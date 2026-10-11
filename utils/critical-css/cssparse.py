"""Minimal CSS splitter for minified vendor CSS.

item = ('stmt', text) | ('rule', id, selector, body) | ('block', prelude, children) | ('raw', prelude, body)
"""
import json
import sys

BS = chr(92)  # backslash


def skip_string(s, i):
    q = s[i]
    i += 1
    while s[i] != q:
        if s[i] == BS:
            i += 1
        i += 1
    return i


def find_block_end(s, i):
    depth = 0
    n = len(s)
    while i < n:
        c = s[i]
        if c in '"\'':
            i = skip_string(s, i)
        elif s.startswith('/*', i):
            i = s.index('*/', i) + 1
        elif c == '{':
            depth += 1
        elif c == '}':
            depth -= 1
            if depth == 0:
                return i
        i += 1
    raise ValueError('unbalanced')


def parse(s, counter):
    items = []
    i = 0
    n = len(s)
    while i < n:
        while i < n and (s[i].isspace() or s.startswith('/*', i)):
            i = s.index('*/', i) + 2 if s.startswith('/*', i) else i + 1
        if i >= n:
            break
        j = i
        while j < n and s[j] not in '{;':
            if s[j] in '"\'':
                j = skip_string(s, j)
            j += 1
        prelude = s[i:j].strip()
        if j >= n:
            break
        if s[j] == ';':
            items.append(('stmt', prelude + ';'))
            i = j + 1
            continue
        end = find_block_end(s, j)
        body = s[j + 1:end]
        low = prelude.lower()
        if low.startswith(('@media', '@supports', '@container', '@layer')):
            items.append(('block', prelude, parse(body, counter)))
        elif low.startswith('@'):
            items.append(('raw', prelude, body))
        else:
            counter[0] += 1
            items.append(('rule', counter[0], prelude, body))
        i = end + 1
    return items


def walk_rules(items):
    for it in items:
        if it[0] == 'rule':
            yield it
        elif it[0] == 'block':
            yield from walk_rules(it[2])


def emit(items, keep):
    out = []
    for it in items:
        if it[0] == 'stmt':
            if not it[1].lower().startswith('@charset'):
                out.append(it[1])
        elif it[0] == 'raw':
            out.append(it[1] + '{' + it[2] + '}')  # @keyframes etc. are always kept
        elif it[0] == 'rule':
            if it[1] in keep:
                out.append(it[2] + '{' + it[3] + '}')
        else:
            inner = emit(it[2], keep)
            if inner:
                out.append(it[1] + '{' + inner + '}')
    return ''.join(out)


def load(path):
    return parse(open(path, encoding='utf8').read(), [0])


if __name__ == '__main__':
    src = open(sys.argv[1], encoding='utf8').read()
    tree = parse(src, [0])
    rules = [{'id': r[1], 's': r[2]} for r in walk_rules(tree)]
    json.dump(rules, open(sys.argv[2], 'w', encoding='utf8'))
    allids = {r['id'] for r in rules}
    print('rules', len(rules), 'src', len(src), 'reemit', len(emit(tree, allids)))

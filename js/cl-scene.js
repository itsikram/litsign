/* Channel letter builder: wall background and day / night view.
 *
 * Loaded after js/cl.js (cl_builderr.php enqueues it) and reads that script's top-level
 * stage, layer, background (the white Konva rect), nodeLists, store, dpi and the size
 * arrow groups. js/cl.js itself is not changed.
 *
 * Background: a layer behind the Konva canvas (#container > .cl-backdrop). The walls are
 * seamless tiles drawn at real size (img/cl-backgrounds, 16 px per inch, made by
 * hero-plan/cl-builder-backgrounds-2026-11-23/make_backgrounds.py). They follow the canvas
 * pan and zoom, so a 12" letter is always as tall as 4-1/2 brick courses. A visitor's own
 * photo fills the view when it is added and then moves with the design as well; it is
 * read in the browser only (kept in this browser's storage, never uploaded). "White" is the
 * builder's original plain canvas and the default.
 *
 * Night view (only when the product sells LED lights): letters and shapes are drawn lit for
 * the product's lighting - glowing face (front lit), glow on the wall behind (back / halo
 * lit) or both (front & back lit, also Type 1's "Front and Back Lit" option) - on a darkened
 * wall. With "No LED" chosen they stay dark. Only the drawing changes: node attributes, the
 * store, prices, drafts and saved designs are untouched, and the design picture uploaded on
 * Save design / Add to cart is always the plain white daytime canvas.
 */
(function () {
  "use strict";

  const root = document.getElementById("clScene");
  const box = document.getElementById("container");
  if (
    !root ||
    !box ||
    !window.Konva ||
    typeof stage === "undefined" ||
    typeof layer === "undefined" ||
    typeof background === "undefined"
  ) {
    return;
  }

  const PX_PER_INCH = typeof dpi === "number" && dpi > 0 ? dpi : 10;
  const SAVE_KEY = "sfs_cl_scene_v1";
  const PHOTO_KEY = "sfs_cl_scene_photo_v1";
  const PHOTO_MAX_SIDE = 2000;
  const PHOTO_MAX_BYTES = 30 * 1024 * 1024;
  const hasLed = root.hasAttribute("data-led");
  const productLighting = root.getAttribute("data-lighting") || "front";

  const toggleBtn = document.getElementById("clSceneBgBtn");
  const panel = document.getElementById("clScenePanel");
  const swatch = root.querySelector(".cl-scene-swatch");
  const bgLabel = root.querySelector(".cl-scene-bg-name");
  const options = Array.prototype.slice.call(root.querySelectorAll(".cl-scene-opt[data-bg]"));
  const photoOption = root.querySelector('.cl-scene-opt[data-bg="photo"]');
  const photoThumb = photoOption ? photoOption.querySelector(".cl-scene-thumb") : null;
  const photoInput = document.getElementById("clScenePhotoInput");
  const photoActions = root.querySelector(".cl-scene-photo-actions");
  const statusEl = document.getElementById("clSceneStatus");
  const timeButtons = Array.prototype.slice.call(root.querySelectorAll("[data-time]"));
  const caption = root.querySelector(".cl-scene-caption");

  // "white" and the preset walls ("photo" is the visitor's own, kept in state.photo).
  const walls = {};
  options.forEach((btn) => {
    const slug = btn.getAttribute("data-bg");
    if (slug === "photo") {
      return;
    }
    const tile = (btn.getAttribute("data-tile") || "").split("x").map(parseFloat);
    walls[slug] = {
      name: btn.getAttribute("data-name") || slug,
      src: btn.getAttribute("data-src") || "",
      thumb: btn.getAttribute("data-thumb") || "",
      tile: tile.length === 2 && tile[0] > 0 && tile[1] > 0 ? tile : null,
    };
  });

  const state = { bg: "white", time: "day", photo: null };
  let designShot = false; // true while js/cl.js takes the design picture

  // ------------------------------------------------------------ storage (this browser only)

  function readStorage(key) {
    try {
      const raw = window.localStorage.getItem(key);
      return raw ? JSON.parse(raw) : null;
    } catch (err) {
      return null;
    }
  }

  function writeStorage(key, value) {
    try {
      if (value === null) {
        window.localStorage.removeItem(key);
      } else {
        window.localStorage.setItem(key, JSON.stringify(value));
      }
      return true;
    } catch (err) {
      return false;
    }
  }

  function saveChoice() {
    writeStorage(SAVE_KEY, { bg: state.bg });
  }

  function savePhoto() {
    // A photo that does not fit in storage still works until the page is reloaded.
    if (!state.photo || !writeStorage(PHOTO_KEY, state.photo)) {
      writeStorage(PHOTO_KEY, null);
    }
  }

  // ------------------------------------------------------------ backdrop layer

  const backdrop = document.createElement("div");
  backdrop.className = "cl-backdrop";
  backdrop.setAttribute("aria-hidden", "true");
  const wallEl = document.createElement("div");
  wallEl.className = "cl-backdrop-wall";
  backdrop.appendChild(wallEl);
  box.insertBefore(backdrop, box.firstChild);
  // The controls sit over the canvas, so they keep their place at every canvas height.
  box.appendChild(root);

  function viewRect() {
    const scale = stage.scaleX() || 1;
    return {
      x: -stage.x() / scale,
      y: -stage.y() / scale,
      width: stage.width() / scale,
      height: stage.height() / scale,
    };
  }

  // Photo placed to fill what the canvas shows now (design px), keeping its proportions.
  function coverView(width, height) {
    const view = viewRect();
    const ratio = width / height;
    let w = view.width;
    let h = w / ratio;
    if (h < view.height) {
      h = view.height;
      w = h * ratio;
    }
    return { x: view.x + (view.width - w) / 2, y: view.y + (view.height - h) / 2, w, h };
  }

  let syncQueued = false;
  function sync() {
    syncQueued = false;
    const scale = stage.scaleX() || 1;
    const x = stage.x();
    const y = stage.y();
    if (state.bg === "photo" && state.photo) {
      const p = state.photo;
      wallEl.style.backgroundSize = p.w * scale + "px " + p.h * scale + "px";
      wallEl.style.backgroundPosition = x + p.x * scale + "px " + (y + p.y * scale) + "px";
      return;
    }
    const wall = walls[state.bg];
    if (wall && wall.tile) {
      // Whole pixels, so the browser never leaves a hairline between tiles.
      const w = Math.max(4, Math.round(wall.tile[0] * PX_PER_INCH * scale));
      const h = Math.max(4, Math.round(wall.tile[1] * PX_PER_INCH * scale));
      wallEl.style.backgroundSize = w + "px " + h + "px";
      wallEl.style.backgroundPosition = Math.round(x) + "px " + Math.round(y) + "px";
    }
  }

  function queueSync() {
    if (!syncQueued) {
      syncQueued = true;
      Promise.resolve().then(sync);
    }
  }

  // js/cl.js moves and zooms the stage in many places (zoom buttons, auto zoom, resize); follow
  // the stage itself instead of each of them.
  stage.on(
    "xChange.clScene yChange.clScene scaleXChange.clScene scaleYChange.clScene widthChange.clScene heightChange.clScene",
    queueSync,
  );

  // ------------------------------------------------------------ lighting at night

  function extras() {
    try {
      return store.getState().extras || {};
    } catch (err) {
      return {};
    }
  }

  // "front" | "back" | "halo" | "dual" | "off"
  function lighting() {
    const ex = extras();
    if (ex.cable && Number(ex.cable.qty) === 0) {
      return "off";
    }
    if (productLighting === "front" && ex.lit && /back/i.test(String(ex.lit.value || ""))) {
      return "dual"; // Type 1 with "Front and Back Lit"
    }
    return productLighting;
  }

  const colorCache = new Map();
  function rgbOf(color) {
    if (typeof color !== "string" || !color) {
      return null;
    }
    if (colorCache.has(color)) {
      return colorCache.get(color);
    }
    // Option data has a stray "vrgb(" in one place; Konva would read that as black.
    const rgba = Konva.Util.colorToRGBA(color.replace(/^v(?=rgb)/i, ""));
    const value = rgba && color !== "transparent" && rgba.a !== 0 ? [rgba.r, rgba.g, rgba.b] : null;
    colorCache.set(color, value);
    return value;
  }

  function toHsl(rgb) {
    const r = rgb[0] / 255;
    const g = rgb[1] / 255;
    const b = rgb[2] / 255;
    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    const l = (max + min) / 2;
    if (max === min) {
      return [0, 0, l];
    }
    const d = max - min;
    const s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
    let h;
    if (max === r) {
      h = (g - b) / d + (g < b ? 6 : 0);
    } else if (max === g) {
      h = (b - r) / d + 2;
    } else {
      h = (r - g) / d + 4;
    }
    return [h / 6, s, l];
  }

  // rgb() string: Konva 8 cannot read hsl() colors for shadows.
  function hslCss(h, s, l) {
    if (s === 0) {
      const v = Math.round(l * 255);
      return "rgb(" + v + "," + v + "," + v + ")";
    }
    const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
    const p = 2 * l - q;
    const channel = (t) => {
      t = t < 0 ? t + 1 : t > 1 ? t - 1 : t;
      if (t < 1 / 6) {
        return p + (q - p) * 6 * t;
      }
      if (t < 1 / 2) {
        return q;
      }
      if (t < 2 / 3) {
        return p + (q - p) * (2 / 3 - t) * 6;
      }
      return p;
    };
    return (
      "rgb(" +
      Math.round(channel(h + 1 / 3) * 255) +
      "," +
      Math.round(channel(h) * 255) +
      "," +
      Math.round(channel(h - 1 / 3) * 255) +
      ")"
    );
  }

  function mixCss(rgb, toward, amount) {
    const c = rgb.map((v, i) => Math.round(v + (toward[i] - v) * amount));
    return "rgb(" + c[0] + "," + c[1] + "," + c[2] + ")";
  }

  // Lit acrylic / translucent vinyl: brighter, a little richer. Blockout black lets no light through.
  function litFace(color) {
    const rgb = rgbOf(color);
    if (!rgb) {
      return { face: null, glow: "rgba(255,250,240,1)", strength: 1 };
    }
    if (rgb[0] + rgb[1] + rgb[2] === 0) {
      return null;
    }
    const hsl = toHsl(rgb);
    if (hsl[2] > 0.9 && hsl[1] < 0.35) {
      return { face: "#ffffff", glow: "rgba(255,252,245,1)", strength: 1 };
    }
    const dark = hsl[2] < 0.2;
    const l = dark ? 0.32 : Math.min(0.72, hsl[2] + (0.64 - hsl[2]) * 0.55);
    const s = Math.min(1, hsl[1] * 1.08 + 0.04);
    return {
      face: hslCss(hsl[0], s, l),
      glow: hslCss(hsl[0], Math.min(1, s + 0.1), Math.max(0.42, Math.min(0.62, l))),
      strength: dark ? 0.45 : 1,
    };
  }

  const HALO = "rgba(255,244,226,1)";
  const NIGHT_ENVIRONMENT = [8, 12, 20];

  function unlit(color, amount) {
    const rgb = rgbOf(color);
    return rgb ? mixCss(rgb, NIGHT_ENVIRONMENT, amount) : color;
  }

  let elementKinds = new Map();
  // While a finger or the mouse drags, resizes or pinches, the night glow is drawn with fewer,
  // smaller blurs so the canvas keeps up on phones; the full glow is drawn when the gesture ends.
  let lightGlow = false;
  function gestureActive() {
    try {
      return typeof clCanvasGestureActive === "function" && clCanvasGestureActive();
    } catch (err) {
      return false;
    }
  }

  function rebuildKinds() {
    lightGlow = state.time === "night" && gestureActive();
    elementKinds = new Map();
    if (typeof nodeLists === "undefined" || !Array.isArray(nodeLists)) {
      return;
    }
    nodeLists.forEach((item) => {
      if (item && item.node) {
        elementKinds.set(item.node, item.type === "raceway" || item.node.getAttr("textIndex") ? "raceway" : "letters");
      }
    });
  }

  function isSizeArrow(node) {
    const parent = node.getParent();
    if (!parent || parent === layer) {
      return false;
    }
    return (
      (typeof widthArrows !== "undefined" && parent === widthArrows) ||
      (typeof heightArrows !== "undefined" && parent === heightArrows)
    );
  }

  // Letter height (text) or the smaller side (shapes), in canvas px.
  function elementSize(node) {
    try {
      if (node.getClassName() === "Text") {
        return node.fontSize() * Math.abs(node.scaleY());
      }
      const rect = node.getSelfRect();
      return Math.max(4, Math.min(rect.width * Math.abs(node.scaleX()), rect.height * Math.abs(node.scaleY())));
    } catch (err) {
      return 80;
    }
  }

  const NONE = {};
  const CACHE_KEYS = ["hasShadow", "shadowRGBA", "hasFill", "hasStroke", "absoluteOpacity"];

  function clearDrawCaches(node) {
    CACHE_KEYS.forEach((key) => node._clearCache(key));
  }

  // Draws the shape once per pass with some attributes swapped. Attributes are written
  // straight into node.attrs (no change events, no redraw request) and put back afterwards.
  const COLOR_KEYS = { fill: true, stroke: true, shadowColor: true };
  const readable = new Map();
  function isReadableColor(value) {
    if (typeof value !== "string") {
      return true;
    }
    if (!readable.has(value)) {
      readable.set(value, !!Konva.Util.colorToRGBA(value));
    }
    return readable.get(value);
  }

  // A pass may carry `shift: [dx, dy]` (canvas units) to draw the shape moved by that much.
  // Every pass skips Konva's "perfect draw" (a full-canvas copy for a shape with fill, stroke and
  // shadow): on phones that copy made each finger move during a night drag or resize take 50-200 ms.
  const FAST_DRAW = { perfectDrawEnabled: false };
  function drawPasses(node, passes, draw) {
    const attrs = node.attrs;
    passes.forEach((onePass) => {
      const pass = Object.assign({}, FAST_DRAW, onePass);
      const saved = {};
      Object.keys(pass).forEach((key) => {
        if (key === "shift" || (COLOR_KEYS[key] && !isReadableColor(pass[key]))) {
          return; // a color Konva cannot read would stop the whole canvas from drawing
        }
        saved[key] = Object.prototype.hasOwnProperty.call(attrs, key) ? attrs[key] : NONE;
        attrs[key] = pass[key];
      });
      if (pass.shift) {
        ["x", "y"].forEach((key, i) => {
          saved[key] = Object.prototype.hasOwnProperty.call(attrs, key) ? attrs[key] : NONE;
          attrs[key] = (Number(attrs[key]) || 0) + pass.shift[i];
        });
        node._clearCache("transform");
        node._clearCache("absoluteTransform");
      }
      clearDrawCaches(node);
      try {
        draw();
      } finally {
        Object.keys(saved).forEach((key) => {
          if (saved[key] === NONE) {
            delete attrs[key];
          } else {
            attrs[key] = saved[key];
          }
        });
        if (pass.shift) {
          node._clearCache("transform");
          node._clearCache("absoluteTransform");
        }
        clearDrawCaches(node);
      }
    });
  }

  function glowPass(color, blur, opacity, scale) {
    return {
      fill: color,
      fillPatternImage: null,
      strokeEnabled: false,
      shadowEnabled: true,
      shadowColor: color,
      shadowBlur: blur / scale,
      shadowOffsetX: 0,
      shadowOffsetY: 0,
      shadowOpacity: 1,
      opacity: opacity,
    };
  }

  function nightPasses(node, kind) {
    const fill = node.fill();
    const stroke = node.stroke();
    const nodeScale = Math.max(0.01, Math.min(Math.abs(node.scaleX()), Math.abs(node.scaleY())));
    // js/cl.js draws the return (the letter's sides) as the shadow, offset down-right, in the
    // return color ("Return" / "Return & Back Color"). LEDs do not light it, so at night it
    // keeps its color, only dimmer.
    const returnColor = node.shadowColor();
    const nightReturn = rgbOf(returnColor) ? unlit(returnColor, 0.42) : "#000000";

    if (kind === "raceway") {
      return [{ fill: unlit(fill, 0.72), shadowColor: nightReturn }];
    }

    const mode = lighting();
    const size = elementSize(node);
    // Glow sizes in canvas px; very large letters are capped so drawing stays quick.
    const viewScale = Math.max(0.05, stage.scaleX() || 1);
    const cap = (px) => Math.min(px, 110 / viewScale);
    const face = mode === "off" ? null : litFace(fill);

    // The return gets its own pass, drawn after the glow (as a shadow under the face it would
    // vanish in the glow): the letter in the return color, moved by the return's offset.
    const offsetX = (Number(node.shadowOffsetX()) || 0) * node.scaleX();
    const offsetY = (Number(node.shadowOffsetY()) || 0) * node.scaleY();
    const returnPass =
      node.shadowEnabled() !== false && (offsetX || offsetY)
        ? {
            shift: [offsetX, offsetY],
            fill: nightReturn,
            fillPatternImage: null,
            shadowEnabled: (Number(node.shadowBlur()) || 0) > 0,
            shadowColor: nightReturn,
            shadowOffsetX: 0,
            shadowOffsetY: 0,
            shadowOpacity: 1,
            opacity: 1,
          }
        : null;
    if (returnPass && typeof stroke === "string" && rgbOf(stroke)) {
      returnPass.stroke = nightReturn;
    }

    // An unlit face (back / halo lit, or no LED) keeps its own color in the evening light:
    // dimmer than by day and darker than the glow on the wall, but still clearly that color.
    const darkFace = { shadowEnabled: false };
    if (typeof fill === "string") {
      darkFace.fill = unlit(fill, 0.55);
    }
    if (typeof stroke === "string") {
      darkFace.stroke = unlit(stroke, 0.45);
    }

    const passes = [];
    const lit = !(mode === "off" || (!face && mode === "front"));
    if (lit && (mode === "back" || mode === "halo" || mode === "dual")) {
      // LEDs behind the letter wash the wall: brightest right at the edge, fading out.
      if (lightGlow) {
        passes.push(glowPass(HALO, cap(size * 0.24), 1, nodeScale));
      } else {
        passes.push(glowPass(HALO, cap(size * 0.5), 0.85, nodeScale));
        passes.push(glowPass(HALO, cap(size * 0.2), 1, nodeScale));
        passes.push(glowPass(HALO, cap(size * 0.07), 1, nodeScale));
      }
    }
    const faceLit = lit && (mode === "front" || mode === "dual") && face;
    if (faceLit) {
      if (lightGlow) {
        passes.push(glowPass(face.glow, cap(size * 0.14), 0.95 * face.strength, nodeScale));
      } else {
        passes.push(glowPass(face.glow, cap(size * 0.34), 0.85 * face.strength, nodeScale));
        passes.push(glowPass(face.glow, cap(size * 0.08), 0.95 * face.strength, nodeScale));
      }
    }
    if (returnPass) {
      passes.push(returnPass);
    }
    if (faceLit) {
      const litPass = { shadowEnabled: false };
      if (face.face) {
        litPass.fill = face.face;
      }
      if (typeof stroke === "string") {
        litPass.stroke = unlit(stroke, 0.4);
      }
      passes.push(litPass);
    } else {
      // Back / halo lit, or no LED: the face itself is not lit.
      passes.push(darkFace);
    }
    return passes;
  }

  function sizeArrowPass(node) {
    // The labels cancel the zoom with their own scale (js/cl.js placeSizeArrows), the lines do not.
    const scale = Math.max(0.05, Math.abs(node.getAbsoluteScale().x) || 1);
    if (state.time === "night") {
      return {
        fill: "#f4f7fb",
        stroke: node.stroke() ? "#f4f7fb" : node.stroke(),
        shadowEnabled: true,
        shadowColor: "#000000",
        shadowBlur: 4 / scale,
        shadowOffsetX: 0,
        shadowOffsetY: 0,
        shadowOpacity: 0.9,
      };
    }
    return {
      shadowEnabled: true,
      shadowColor: "#ffffff",
      shadowBlur: 4 / scale,
      shadowOffsetX: 0,
      shadowOffsetY: 0,
      shadowOpacity: 1,
    };
  }

  layer.on("beforeDraw.clScene", rebuildKinds);

  // Back to the full glow once the finger or mouse button is up.
  let fullGlowFrame = null;
  ["touchend", "touchcancel", "mouseup", "pointerup", "pointercancel"].forEach((type) => {
    window.addEventListener(
      type,
      () => {
        if (!lightGlow || fullGlowFrame !== null) {
          return;
        }
        fullGlowFrame = requestAnimationFrame(() => {
          fullGlowFrame = null;
          if (lightGlow && !gestureActive()) {
            layer.batchDraw();
          }
        });
      },
      { passive: true },
    );
  });

  let lookFailed = false; // after an error the canvas falls back to the builder's own drawing
  const shapeDrawScene = Konva.Shape.prototype.drawScene;
  Konva.Shape.prototype.drawScene = function (can, top) {
    if (lookFailed || designShot || this.getLayer() !== layer || (state.time === "day" && state.bg === "white")) {
      return shapeDrawScene.call(this, can, top);
    }
    const draw = () => shapeDrawScene.call(this, can, top);
    let passes = null;
    try {
      if (isSizeArrow(this)) {
        passes = [sizeArrowPass(this)];
      } else if (state.time === "night" && elementKinds.has(this)) {
        passes = nightPasses(this, elementKinds.get(this));
      }
    } catch (err) {
      passes = null;
    }
    if (!passes) {
      return draw();
    }
    try {
      drawPasses(this, passes, draw);
    } catch (err) {
      lookFailed = true;
      if (window.console) {
        console.warn("cl-scene: night view switched off after a drawing error", err);
      }
      layer.batchDraw();
    }
    return this;
  };

  // The saved / cart design picture: plain white canvas, daytime colors.
  const stageToDataURL = stage.toDataURL;
  stage.toDataURL = function () {
    const wasVisible = background.visible();
    designShot = true;
    background.visible(true);
    try {
      return stageToDataURL.apply(this, arguments);
    } finally {
      designShot = false;
      background.visible(wasVisible);
      layer.batchDraw();
    }
  };

  // ------------------------------------------------------------ applying a choice

  const CAPTIONS = {
    front: "Lit face at night",
    back: "Back lit: glow on the wall",
    halo: "Halo lit: glow on the wall",
    dual: "Front & back lit at night",
    off: "No LED chosen: letters stay dark",
  };

  function currentName() {
    if (state.bg === "photo") {
      return "Your photo";
    }
    return walls[state.bg] ? walls[state.bg].name : "White";
  }

  function apply() {
    const night = hasLed && state.time === "night";
    const wall = walls[state.bg];
    box.classList.toggle("cl-scene-night", night);
    box.setAttribute("data-scene-bg", state.bg);

    if (state.bg === "photo" && state.photo) {
      wallEl.style.backgroundImage = 'url("' + state.photo.src + '")';
      wallEl.style.backgroundRepeat = "no-repeat";
    } else if (wall && wall.src) {
      wallEl.style.backgroundImage = 'url("' + wall.src + '")';
      wallEl.style.backgroundRepeat = "repeat";
    } else {
      wallEl.style.backgroundImage = "none";
    }

    // The Konva white rect is the original canvas: only for the plain white daytime view.
    const plain = state.bg === "white" && !night;
    if (background.visible() !== plain) {
      background.visible(plain);
    }

    options.forEach((btn) => {
      btn.setAttribute("aria-pressed", btn.getAttribute("data-bg") === state.bg ? "true" : "false");
    });
    timeButtons.forEach((btn) => {
      btn.setAttribute("aria-pressed", btn.getAttribute("data-time") === state.time ? "true" : "false");
    });
    if (swatch) {
      const thumb = state.bg === "photo" && state.photo ? state.photo.src : wall ? wall.thumb : "";
      swatch.style.backgroundImage = thumb ? 'url("' + thumb + '")' : "";
      swatch.classList.toggle("is-white", !thumb);
    }
    if (bgLabel) {
      bgLabel.textContent = currentName();
    }
    if (toggleBtn) {
      toggleBtn.setAttribute("aria-label", "Background: " + currentName());
    }
    if (photoOption) {
      photoOption.hidden = !state.photo;
      if (photoThumb && state.photo) {
        photoThumb.style.backgroundImage = 'url("' + state.photo.src + '")';
      }
    }
    if (photoActions) {
      photoActions.hidden = !(state.photo && state.bg === "photo");
    }
    updateCaption();
    sync();
    layer.batchDraw();
  }

  function updateCaption() {
    if (!caption) {
      return;
    }
    const show = hasLed && state.time === "night";
    caption.hidden = !show;
    if (show) {
      const mode = lighting();
      caption.textContent = CAPTIONS[mode] || CAPTIONS.front;
      caption.classList.toggle("is-off", mode === "off");
    }
  }

  function setStatus(message) {
    if (!statusEl) {
      return;
    }
    statusEl.textContent = message || "";
    statusEl.hidden = !message;
  }

  function chooseBackground(slug) {
    if (slug === "photo" && !state.photo) {
      return;
    }
    if (slug !== "photo" && !walls[slug]) {
      return;
    }
    state.bg = slug;
    setStatus("");
    saveChoice();
    apply();
  }

  function setTime(time) {
    if (!hasLed || (time !== "day" && time !== "night") || state.time === time) {
      return;
    }
    state.time = time;
    apply();
  }

  // Redraw when the LED, lighting or face choices change while the night view is on.
  let lastLighting = lighting();
  store.subscribe(() => {
    const mode = lighting();
    if (mode !== lastLighting) {
      lastLighting = mode;
      if (state.time === "night") {
        updateCaption();
        layer.batchDraw();
      }
    }
  });

  // ------------------------------------------------------------ the visitor's own photo

  function loadImage(file) {
    return new Promise((resolve, reject) => {
      const url = URL.createObjectURL(file);
      const img = new Image();
      img.onload = () => resolve({ img, url });
      img.onerror = () => {
        URL.revokeObjectURL(url);
        reject(new Error("unreadable"));
      };
      img.src = url;
    });
  }

  async function usePhoto(file) {
    if (!file) {
      return;
    }
    if (!/^image\//i.test(file.type || "")) {
      setStatus("Please choose a photo (JPG, PNG or WebP).");
      return;
    }
    if (file.size > PHOTO_MAX_BYTES) {
      setStatus("That photo is over 30 MB. Please choose a smaller one.");
      return;
    }
    setStatus("Adding your photo…");
    root.classList.add("is-busy");
    try {
      const { img, url } = await loadImage(file);
      const width = img.naturalWidth;
      const height = img.naturalHeight;
      if (!width || !height) {
        throw new Error("empty");
      }
      // Smaller copy: quick to draw, and it fits in this browser's storage for next time.
      const k = Math.min(1, PHOTO_MAX_SIDE / Math.max(width, height));
      const canvas = document.createElement("canvas");
      canvas.width = Math.max(1, Math.round(width * k));
      canvas.height = Math.max(1, Math.round(height * k));
      const ctx = canvas.getContext("2d");
      ctx.fillStyle = "#ffffff";
      ctx.fillRect(0, 0, canvas.width, canvas.height);
      ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
      URL.revokeObjectURL(url);
      const src = canvas.toDataURL("image/jpeg", 0.85);
      const place = coverView(canvas.width, canvas.height);
      state.photo = { src, x: place.x, y: place.y, w: place.w, h: place.h, pw: canvas.width, ph: canvas.height };
      state.bg = "photo";
      savePhoto();
      saveChoice();
      setStatus("");
      apply();
    } catch (err) {
      setStatus("That photo could not be opened. Please try a JPG or PNG.");
    } finally {
      root.classList.remove("is-busy");
    }
  }

  function fitPhoto() {
    if (!state.photo) {
      return;
    }
    const place = coverView(state.photo.pw || state.photo.w, state.photo.ph || state.photo.h);
    Object.assign(state.photo, { x: place.x, y: place.y, w: place.w, h: place.h });
    savePhoto();
    apply();
  }

  function removePhoto() {
    state.photo = null;
    writeStorage(PHOTO_KEY, null);
    if (state.bg === "photo") {
      state.bg = "white";
    }
    saveChoice();
    apply();
    if (toggleBtn && isOpen()) {
      const pressed = root.querySelector('.cl-scene-opt[aria-pressed="true"]');
      (pressed || toggleBtn).focus();
    }
  }

  // ------------------------------------------------------------ panel

  function isOpen() {
    return panel && !panel.hidden;
  }

  // The panel opens upward from the Background button and must stay inside the canvas.
  function fitPanel() {
    if (panel && toggleBtn) {
      const room = toggleBtn.getBoundingClientRect().top - box.getBoundingClientRect().top - 18;
      panel.style.maxHeight = Math.max(140, Math.round(room)) + "px";
    }
  }

  function openPanel() {
    if (!panel || isOpen()) {
      return;
    }
    fitPanel();
    panel.hidden = false;
    toggleBtn.setAttribute("aria-expanded", "true");
    document.body.classList.add("cl-scene-open");
    const pressed = panel.querySelector('.cl-scene-opt[aria-pressed="true"]:not([hidden])');
    if (pressed) {
      pressed.focus({ preventScroll: true });
    }
  }

  function closePanel(returnFocus) {
    if (!panel || !isOpen()) {
      return;
    }
    panel.hidden = true;
    toggleBtn.setAttribute("aria-expanded", "false");
    document.body.classList.remove("cl-scene-open");
    if (returnFocus) {
      toggleBtn.focus({ preventScroll: true });
    }
  }

  if (toggleBtn && panel) {
    toggleBtn.addEventListener("click", () => (isOpen() ? closePanel(false) : openPanel()));
    document.addEventListener("pointerdown", (event) => {
      if (isOpen() && !root.contains(event.target)) {
        closePanel(false);
      }
    });
    root.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && isOpen()) {
        event.stopPropagation();
        closePanel(true);
      }
    });
  }

  // Clicks inside the controls must not reach the canvas (no deselecting, no dragging).
  ["pointerdown", "mousedown", "touchstart", "click", "dblclick", "wheel"].forEach((type) => {
    root.addEventListener(type, (event) => event.stopPropagation(), { passive: type === "touchstart" || type === "wheel" });
  });

  options.forEach((btn) => {
    btn.addEventListener("click", () => chooseBackground(btn.getAttribute("data-bg")));
  });

  // Arrow keys move between the backgrounds.
  if (panel) {
    panel.addEventListener("keydown", (event) => {
      const keys = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 };
      if (!(event.key in keys) || !event.target.classList.contains("cl-scene-opt")) {
        return;
      }
      const visible = options.filter((btn) => !btn.hidden);
      const index = visible.indexOf(event.target);
      if (index < 0) {
        return;
      }
      event.preventDefault();
      visible[(index + keys[event.key] + visible.length) % visible.length].focus();
    });
  }

  if (photoInput) {
    photoInput.addEventListener("change", () => {
      const file = photoInput.files && photoInput.files[0];
      photoInput.value = "";
      usePhoto(file);
    });
  }

  root.querySelectorAll("[data-scene-action]").forEach((btn) => {
    btn.addEventListener("click", () => {
      const action = btn.getAttribute("data-scene-action");
      if (action === "fit-photo") {
        fitPhoto();
      } else if (action === "remove-photo") {
        removePhoto();
      }
    });
  });

  timeButtons.forEach((btn) => {
    btn.addEventListener("click", () => setTime(btn.getAttribute("data-time")));
  });

  window.addEventListener("resize", () => {
    if (isOpen()) {
      fitPanel();
    }
  });

  // ------------------------------------------------------------ start

  const savedPhoto = readStorage(PHOTO_KEY);
  if (
    savedPhoto &&
    typeof savedPhoto.src === "string" &&
    savedPhoto.src.indexOf("data:image/") === 0 &&
    [savedPhoto.x, savedPhoto.y, savedPhoto.w, savedPhoto.h].every(Number.isFinite) &&
    savedPhoto.w > 0 &&
    savedPhoto.h > 0
  ) {
    state.photo = savedPhoto;
  }
  const saved = readStorage(SAVE_KEY);
  if (saved && typeof saved.bg === "string" && (walls[saved.bg] || (saved.bg === "photo" && state.photo))) {
    state.bg = saved.bg;
  }
  root.hidden = false;
  apply();

  // For checks in the browser console.
  window.clScene = {
    state,
    lighting,
    setBackground: chooseBackground,
    setTime,
  };
})();

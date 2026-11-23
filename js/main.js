(function ($) {
  // Product group buttons (js/product-group-swap.js) swap another product into the page
  // and fire "wholesale:product-swapped"; everything below then runs again on the new
  // product, except the header, menu and scroll-reveal code (first run only). Each run's
  // document-level product handlers end before the next run binds its own.
  let pageScope = null;
  const pageInit = (first, e = $) => {
    if (pageScope) pageScope.abort();
    pageScope = new AbortController();
    const signal = pageScope.signal;
    $(document).off('.productPage');

    // Fade sections in as they scroll into view, on desktop only. Phones skip
    // it: visitors there scroll fast, and hidden content read as blank gaps
    // and missing product names (Visitor Insights showed most ad visitors on
    // phones leaving after scrolling a few percent of the page). Only whole
    // sections and cards fade, never their text one element at a time.
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    // While the motion layer is on (inc/motion.php tags <html data-motion>),
    // js/motion.js does the reveals instead of this block.
    const allowReveal = first && !prefersReducedMotion && 'IntersectionObserver' in window
      && !document.documentElement.hasAttribute('data-motion')
      && window.matchMedia('(min-width: 992px) and (hover: hover)').matches;
    const revealRoots = ['main', '#content', '.home', '.landing-page', '.product-details', '.contact-page', '.site-main'];
    const revealTargets = ['section', '.card', '.sf-type-card', '.cl-step'];
    const revealSelector = revealRoots
      .flatMap((root) => revealTargets.map((target) => `${root} ${target}`))
      .concat('.footer > *')
      .join(',');
    const candidates = allowReveal ? [...new Set(document.querySelectorAll(revealSelector))].filter((element) => {
      return !element.matches('.home-hero, .customer-review-section')
        && !element.closest('header, nav, .modal, .offcanvas, .home-hero, .cla-hero, .cla-quote-wrap, .checkout-page, .cart-page-v2, .account-page-v2, .order-confirmation, .footer-cp-text-container, [aria-hidden="true"]');
    }) : [];
    // A card inside a fading section would wait for both; keep the outermost.
    const candidateSet = new Set(candidates);
    const revealElements = candidates.filter((element) => {
      for (let parent = element.parentElement; parent; parent = parent.parentElement) {
        if (candidateSet.has(parent)) return false;
      }
      return true;
    });

    // Card galleries set up by an earlier run keep their handlers.
    const galleries = [...document.querySelectorAll('[data-gallery]:not([data-gallery-bound])')];
    galleries.forEach((gallery) => { gallery.dataset.galleryBound = ''; });
    const setGalleryImage = (gallery, nextIndex) => {
      const images = [...gallery.querySelectorAll('.pb-gallery-image')];
      const count = gallery.querySelector('.pb-gallery-count');
      if (!images.length) return;

      const activeIndex = Math.max(0, images.findIndex((image) => image.classList.contains('is-active')));
      const nextActiveIndex = (nextIndex + images.length) % images.length;
      const nextImage = images[nextActiveIndex];
      if (!nextImage.getAttribute('src') && nextImage.dataset.gallerySrc) {
        nextImage.src = nextImage.dataset.gallerySrc;
      }
      images.forEach((image, index) => {
        const isActive = index === nextActiveIndex;
        image.classList.toggle('is-active', isActive);
        image.setAttribute('aria-hidden', String(!isActive));
      });
      gallery.dataset.galleryIndex = String(nextActiveIndex);
      if (count) count.textContent = `${nextActiveIndex + 1} / ${images.length}`;
      return activeIndex;
    };

    document.addEventListener('click', (event) => {
      const arrow = event.target.closest('.pb-gallery-arrow');
      if (!arrow) return;
      const gallery = arrow.closest('[data-gallery]');
      if (!gallery) return;

      event.preventDefault();
      event.stopImmediatePropagation();
      const currentIndex = Number(gallery.dataset.galleryIndex || 0);
      const isPrevious = arrow.classList.contains('pb-gallery-prev');
      setGalleryImage(gallery, currentIndex + (isPrevious ? -1 : 1));
    }, { signal });

    galleries.forEach((gallery) => {
      setGalleryImage(gallery, 0);
    });

    // Touch: a horizontal swipe on a card image shows the next/previous photo
    // instead of opening the product.
    galleries.filter((gallery) => gallery.classList.contains('has-gallery')).forEach((gallery) => {
      let startX = 0;
      let startY = 0;
      let swiped = false;
      gallery.addEventListener('touchstart', (event) => {
        startX = event.touches[0].clientX;
        startY = event.touches[0].clientY;
        swiped = false;
      }, { passive: true });
      gallery.addEventListener('touchend', (event) => {
        const dx = event.changedTouches[0].clientX - startX;
        const dy = event.changedTouches[0].clientY - startY;
        if (Math.abs(dx) < 40 || Math.abs(dx) < Math.abs(dy) * 1.5) return;
        swiped = true;
        setGalleryImage(gallery, Number(gallery.dataset.galleryIndex || 0) + (dx < 0 ? 1 : -1));
      }, { passive: true });
      gallery.addEventListener('click', (event) => {
        if (!swiped) return;
        swiped = false;
        event.preventDefault();
      }, true);
    });

    if (revealElements.length) {
      document.documentElement.classList.add('scroll-effects-enabled');
      const viewportHeight = window.innerHeight;
      const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0, rootMargin: '0px 0px 10% 0px' });

      revealElements.forEach((element, index) => {
        // Anything already on screen shows at once instead of fading in late.
        const visibleNow = element.getBoundingClientRect().top < viewportHeight;
        element.classList.add('scroll-reveal');
        if (visibleNow) {
          element.classList.add('is-visible');
          return;
        }
        element.style.setProperty('--scroll-delay', `${Math.min(index % 3, 2) * 60}ms`);
        revealObserver.observe(element);
      });
    }

    // The product discount gives the everyday price and is never shown as a sale; a dated
    // sale (#promoPercent, 0 outside its dates) comes off the everyday price.
    let discountPercent = parseFloat($('#discountPercent').val()) || 0;
    let promoPercent = parseFloat($('#promoPercent').val()) || 0;
    let pricePerSqft = parseFloat($('#pricePerSqft').val()|| 0).toFixed(2)

    function interpolate(x,isCap) {
      const x1 = 8, y1 = 4.37
      const x2 = 45, y2 = 24.73;

      const capx1 = 8, capy1 = 6.33;
      const capx2 = 45, capy2 = 35.65;


      if(isCap) {
        return capy1 + ((x - capx1) * (capy2 - capy1)) / (capx2 - capx1);
      }else {
        return y1 + ((x - x1) * (y2 - y1)) / (x2 - x1);
      }

    }

    let isNumber = (value) => {

      if (typeof value == 'number') return true;
      switch (typeof value) {
        case 'string':
          break;
        case 'object':

          return false;
          break;
        default:
          return false;
      }
      if ((value.toString()).split('/')[0]) {
        if ((value.toString()).split('/')[0] === 'number') return true;

      }
      return typeof value === 'number' && Number.isFinite(value) && !Number.isInteger(value);
    }
    function isCapitalLetter(char) {
      // Check if the character is a single letter and it's uppercase
      return char === char.toUpperCase() && char !== char.toLowerCase();
    }
    function updateAproxWidth(letters, size) {
      let letterCharacters = $("#letterInput").val().replace(" ", "")
      let totalCapLettters = 0;
      let totalLowLetters = parseInt(letterCharacters);
      let totalWidth = 0;

      [...letterCharacters.split('')].forEach(element => {
        let isCap = isCapitalLetter(element)
        if (isCap) {
          totalWidth = totalWidth + interpolate(parseFloat(size), true)
        } else {
          totalWidth = totalWidth + interpolate(parseFloat(size), false)
        }
      });

      $('#widthDisplay').html(`Approximate Width: <b> ${totalWidth.toFixed(1)} Inches</b>`)
    }

    let updatePrice = (subPrice, price,from) => {

      if(isNaN(subPrice) && isNaN(price)) {
        console.log('Something went wrong '+ from)
        return;
      }

      $('.add-to-cart-btn').prop('disabled', parseFloat(price) <= 0);

      function formatNumberWithCommas(number) {
        return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
      }
      // The subtotal and saving rows only show during a dated sale: everyday price, sale saving.
      let everydaySubPrice = subPrice - ((subPrice / 100) * discountPercent)
      let subTotalPrice = (everydaySubPrice).toFixed(2)
      let everydayPrice = discountPercent ? price - ((price / 100) * discountPercent) : price
      let totalPrice = (promoPercent ? everydayPrice - ((everydayPrice / 100) * promoPercent) : everydayPrice).toFixed(2)


      // display saving price
      $(".product-pricing-box .price-subtotal-container  .price-saving").text(
        formatNumberWithCommas(((everydaySubPrice / 100) * promoPercent).toFixed(2))

      );

      // display subtotal
      $(".product-pricing-box .price-subtotal-container  .price-subtotal").text(
        formatNumberWithCommas(subTotalPrice)

      );
      // display total
      $(".product-pricing-box .price-total-container  .price-total").text(
        formatNumberWithCommas(totalPrice)
      );
      // set total cost
      //$("#totalCost").val(totalPrice);
    };


    let numberWithCommas = (x) => {
      return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    function formatDateWithAddedDays(daysToAdd) {
      // Get the current date
      const currentDate = new Date();

      // Add the custom number of days
      currentDate.setDate(currentDate.getDate() + daysToAdd);

      // Array for day names and month names
      const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
      const monthsOfYear = ['Jan.', 'Feb.', 'Mar.', 'Apr.', 'May.', 'Jun.', 'Jul.', 'Aug.', 'Sep.', 'Oct.', 'Nov.', 'Dec.'];

      // Format the date as 'Wed Jul. 10'
      const formattedDate = `${daysOfWeek[currentDate.getDay()]} ${monthsOfYear[currentDate.getMonth()]} ${currentDate.getDate()}`;
      return formattedDate;

    }



    if (!window.matchMedia("(max-width: 768px)").matches) {

      // Storage throws when site data is blocked; that must not stop the rest of this handler.
      try {
        let logoWidth = localStorage.getItem("logoWidth");
        if (logoWidth) {
          $('.header-logo').css({
            height: 'auto',
            width: logoWidth
          })
        } else {
          let productImageWidth = $('.home .product-box').width();
          if (productImageWidth) {
            localStorage.setItem('logoWidth', productImageWidth)
            $('.header-logo').css({
              height: 'auto',
              width: productImageWidth
            })
          }

        }
      } catch (e) {
        // Keep the default logo width.
      }


    }
    $(".form-select option:first-child").attr("selected", true);
    $('#letter-output').addClass('output-default-red');

    // reset Select
    let resetSelect = (e) => {

      $(".select-face option,.select-lit option,.select-raceway option,.select-clear-acrylic option,.select-power-supply option").prop(
        "selected",
        function () {
          $(this).parent().attr('data-ccost', 0)
          return this.defaultSelected;
        }
      );
      $('#productQuantity').val(1)
      setTimeout(() => {
        if ($('#letter-output').text() == 'Enter Your Text') {
          $('#letter-output').css('color', 'red');

        } else {

          let faceColor = $("#select-face option:first-child").attr('value') || $('.face-option-name').attr('data-value');
          let standarPowerSupplyVal = $('.select-power-supply option:nth-child(2)').attr('value') || '0/No'
          $('.select-power-supply').val(standarPowerSupplyVal)
          $('.select-power-supply').trigger('change')
          if (faceColor) {
            $("#letter-output").css("color", faceColor.split("/")[2]);
          } else {
            $('#letter-output').css('color', '#000');

          }

        }

      }, 10)
      $('#letter-output').removeClass('output-default-red');

      
      $("#custom-select-face ul li:first-child").trigger("click");
      $("#custom-select-face ul").hide();
    };

    let resetTurnaround = () => {
      let oldTotalCost = parseFloat($("#totalCost").val() || 0);
      let oldTurnaroundCost = parseFloat($("#turnaroundCost").val() || 0);
      $("#totalCost").val(oldTotalCost - oldTurnaroundCost);
      $('#turnaroundCost').val('0');
      $('#turnaroundNextDay').prop('checked', true);
      console.log(oldTotalCost,oldTurnaroundCost)
      updatePrice(oldTotalCost, oldTotalCost, 'resetTurnaround')
      // $('#turnaroundNextDay').trigger('change')
    }



    let updateDisplay = (height, width) => {
      let totalHeightIn = +(height * 12).toFixed(1);
      let totalWidthIn = +(width * 12).toFixed(1);
      let totalSqft = height * width;
      let pricePerSqft = parseFloat(
        $(".product-attibute-box #pricePerSqft").val()
      );
      let subTotalPrice = pricePerSqft * totalSqft;
      let totalPrice = pricePerSqft * totalSqft;
      // B2Sign-style size box (every size calculator product) reads width x height.
      let widthFirst = $(".product-attibute-box .total-size-sqft").attr("data-order") === "wh";
      // B2Sign style: "6 ft2", "16.67 ft2" (no trailing zeros).
      let dimentionText = `${widthFirst ? totalWidthIn : totalHeightIn}" x ${widthFirst ? totalHeightIn : totalWidthIn}" = ${+totalSqft.toFixed(
        2
      )} ft<sup>2</sup> `;
      // display dimention
      $(".product-attibute-box .total-size-sqft").html(dimentionText);
      $(".product-attibute-box .total-size-sqft").attr(
        "data-total-sqft",
        totalSqft
      );
      updatePrice(subTotalPrice, totalPrice,'updateDisplay');
      // The customer's option choices stay as they are; the live price quote
      // (wholesale_price_quote) prices the size and every chosen option.
    };

    let validateCalcInputs = (e) => {
      let minValue = parseFloat($(e.currentTarget).attr("min"));
      if (parseFloat($(e.currentTarget).val()) < minValue) {
        alert("Mininum: " + minValue);
        e.target.value = minValue;
      }

      let maxValue = parseFloat($(e.currentTarget).attr("max"));
      if (parseFloat($(e.currentTarget).val()) > maxValue) {
        alert("Maximum: " + maxValue);
        e.target.value = maxValue;
      }
    };



    // change attribute
    $(document).on(
      "change.productPage",
      ".product-attibute-box .dynamic-select:not(.avoid-price)",
      (e) => {

        $('#productQuantity').val(1)
        $('#productQuantity').trigger('change')
        let getAttrCurrentCost = parseFloat(
          $(e.currentTarget).attr("data-cCost")
        );
        let currentCcost = parseFloat($(e.currentTarget).attr("data-cCost") || 0)
        let priceBeforeChange = parseFloat($("#totalCost").val()) - currentCcost;
        $("#totalCost").val(priceBeforeChange)
        updatePrice(
          priceBeforeChange - getAttrCurrentCost,
          priceBeforeChange - getAttrCurrentCost
        );

        priceBeforeChange = parseFloat($("#totalCost").val());
        let costType, selectedAttrVal, priceAfterChange, selectedAttrText;
        $("#totalCost").val(priceAfterChange)

        selectedAttrVal = parseFloat(e.target.value) || 0;

        if (e.target.value.split("/")[1]) {
          let separatedValue = e.target.value.split("/")
          selectedAttrVal = parseFloat(separatedValue[0]);

          costType = separatedValue[1];
          selectedAttrText = selectedAttrVal[separatedValue.length - 1];
        }


        if (costType == undefined) {

          let hasDoubleQuotes = selectedAttrVal.length > 0 && selectedAttrVal.includes('”');
          let hasSingleQuotes = selectedAttrVal.length > 0 && selectedAttrVal.includes("'");

          if (hasDoubleQuotes || hasSingleQuotes) {
            console.log("The string contains quotes.");
          } else {
            selectedAttrVal = parseFloat(selectedAttrVal);
            priceAfterChange = priceBeforeChange + selectedAttrVal
            console.log('ctu',priceBeforeChange, selectedAttrVal)
            $(e.currentTarget).attr("data-cCost", selectedAttrVal);
            $("#totalCost").val(priceAfterChange)
            return updatePrice(priceAfterChange, priceAfterChange, '(costType == undefined)')
          }

        }

        // "%total" (percent of the whole item) is close enough here; the server quote corrects it.
        if (costType == "%" || costType == "%total") {
          priceAfterChange =
            priceBeforeChange + ((priceBeforeChange / 100) * selectedAttrVal);

          $(e.currentTarget).attr(
            "data-cCost",
            ((priceBeforeChange / 100) * selectedAttrVal)
          );
          //alert(priceAfterChange)
        } else if (costType == "sqft") {
          let minSqft = parseFloat($('#minSqft').val());
          let totalSqft = parseFloat($('#totalSqft').val())
          if (!$('.dimenstion-calculator').length && minSqft > totalSqft) {
            totalSqft = minSqft;
          }

          //let pricePerSqft = parseFloat($('#pricePerSqft').val())
          let totalSqftPrice = totalSqft * selectedAttrVal;

          priceAfterChange = priceBeforeChange + totalSqftPrice;
          $(e.currentTarget).attr("data-cCost", totalSqftPrice);
        } else if (costType == "lft") {
          let sizeValue = $(".select-height").val();
          let sizeInch = $(".select-height").attr("data-selected-size");
          let letterCharacters = $("#letterInput")
            .val()
            .replace(/\s/g, "").length;
          let totalSizeInch = sizeInch * letterCharacters;
          let toalSizeFt = totalSizeInch / 12;
          let totalLftCost = selectedAttrVal * toalSizeFt;

          priceAfterChange = totalLftCost + priceBeforeChange;
          $(e.currentTarget).attr("data-cCost", totalLftCost);
        } else if (costType == "clin") {
          // Per inch of letter height, per letter (channel letter vinyl faces).
          let sizeInch = parseFloat($(".select-height option:selected").text()) || 0;
          let letterCharacters = $("#letterInput").val().replace(/\s/g, "").length;
          let totalClinCost = selectedAttrVal * sizeInch * letterCharacters;

          priceAfterChange = totalClinCost + priceBeforeChange;
          $(e.currentTarget).attr("data-cCost", totalClinCost);
        } else {

          if (!Number.isNaN(selectedAttrVal)) {
            priceAfterChange = priceBeforeChange + selectedAttrVal
            $(e.currentTarget).attr("data-cCost", selectedAttrVal);
          } else {
            let firstOptionValue = $(e.currentTarget).children().first().val();
            $(e.currentTarget).val(firstOptionValue);
            priceAfterChange = priceBeforeChange 
            $(this).trigger('change')
            $(e.currentTarget).attr("data-cCost", 0);

          }

        }
        resetTurnaround()
        $("#totalCost").val(priceAfterChange)

        updatePrice(priceAfterChange, priceAfterChange, 'on change bottom');


      }
    );
    // 12 inches or more in an inch box: carry the whole feet over to the feet box
    // (18 in -> 1 ft 6 in). Capture phase, so the size and price handlers below
    // already see the moved values.
    document.addEventListener('change', (e) => {
      const inch = e.target;
      if (!inch.matches || !inch.matches('.dimenstion-calculator input[name$="-in"]')) return;
      const value = parseFloat(inch.value);
      if (!(value >= 12)) return;
      const feet = inch.form && inch.form.querySelector('input[name="' + inch.name.replace(/-in$/, '-ft') + '"]');
      if (!feet) return;
      feet.value = (parseFloat(feet.value) || 0) + Math.floor(value / 12);
      inch.value = Math.round((value % 12) * 100) / 100;
    }, { capture: true, signal });

    // Scrolling the page over a focused size box must not change the size.
    document.addEventListener('wheel', (e) => {
      if (e.target === document.activeElement && e.target.matches && e.target.matches('.dimenstion-calculator input[type="number"]')) e.target.blur();
    }, { passive: true, signal });

    // B2Sign-style Size selector (_size_presets products, single-product.php): a preset
    // fills in the width and height, whose boxes stay hidden; "Custom Sizes" shows the
    // boxes. The boxes are what the form posts and the price uses.
    const sizeSelect = document.querySelector('.pb-size-select');
    const sizeForm = sizeSelect && sizeSelect.closest('form');
    if (sizeForm) {
      const sizeRow = sizeSelect.closest('.pb-size-b2');
      const button = sizeSelect.querySelector('.pb-size-select__button');
      const value = sizeSelect.querySelector('.pb-size-select__value');
      const list = sizeSelect.querySelector('.pb-size-select__list');
      const options = [...list.querySelectorAll('[role="option"]')];
      const dimBox = (dim) => sizeForm.querySelector('input[name="' + dim + '-in"]');
      let active = null;
      const isOpen = () => !list.hidden;
      const setActive = (option) => {
        options.forEach((o) => o.classList.toggle('is-active', o === option));
        active = option;
        if (option) {
          list.setAttribute('aria-activedescendant', option.id);
          // Scroll the list only, never the page.
          const top = option.offsetTop;
          const bottom = top + option.offsetHeight;
          if (top < list.scrollTop) list.scrollTop = top;
          else if (bottom > list.scrollTop + list.clientHeight) list.scrollTop = bottom - list.clientHeight;
        }
      };
      const open = () => {
        list.hidden = false;
        button.setAttribute('aria-expanded', 'true');
        sizeSelect.classList.add('is-open');
        setActive(options.find((o) => o.getAttribute('aria-selected') === 'true') || options[0]);
        list.focus({ preventScroll: true });
      };
      const close = (focusButton) => {
        if (!isOpen()) return;
        list.hidden = true;
        button.setAttribute('aria-expanded', 'false');
        sizeSelect.classList.remove('is-open');
        if (focusButton) button.focus();
      };
      const choose = (option) => {
        options.forEach((o) => o.setAttribute('aria-selected', o === option ? 'true' : 'false'));
        value.replaceChildren(...[...option.childNodes].map((node) => node.cloneNode(true)));
        const custom = !!option.dataset.custom;
        sizeRow.classList.toggle('is-preset', !custom);
        close(!custom);
        if (custom) {
          const width = dimBox('width');
          if (width) { width.focus(); width.select(); }
          return;
        }
        // Both values first, so the price handlers never see one side changed alone.
        const boxes = [[dimBox('width'), option.dataset.width], [dimBox('height'), option.dataset.height]].filter(([box]) => box);
        boxes.forEach(([box, size]) => { box.value = size; });
        boxes.forEach(([box]) => {
          box.dispatchEvent(new Event('input', { bubbles: true }));
          box.dispatchEvent(new Event('change', { bubbles: true }));
        });
      };
      button.addEventListener('click', () => (isOpen() ? close(true) : open()));
      button.addEventListener('keydown', (e) => {
        if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(e.key)) {
          e.preventDefault();
          open();
        }
      });
      list.addEventListener('keydown', (e) => {
        const at = options.indexOf(active);
        if (e.key === 'ArrowDown') setActive(options[Math.min(options.length - 1, at + 1)]);
        else if (e.key === 'ArrowUp') setActive(options[Math.max(0, at - 1)]);
        else if (e.key === 'Home') setActive(options[0]);
        else if (e.key === 'End') setActive(options[options.length - 1]);
        else if ((e.key === 'Enter' || e.key === ' ') && active) choose(active);
        else if (e.key === 'Escape') close(true);
        else if (e.key === 'Tab') { close(false); return; }
        else return;
        e.preventDefault();
      });
      list.addEventListener('click', (e) => {
        const option = e.target.closest('[role="option"]');
        if (option) choose(option);
      });
      list.addEventListener('mousemove', (e) => {
        const option = e.target.closest('[role="option"]');
        if (option && option !== active) setActive(option);
      });
      document.addEventListener('click', (e) => { if (!sizeSelect.contains(e.target)) close(false); }, { signal });
    }

    // Size box in feet and inches (meta _size_unit = ftin, like B2Sign's banner page): the visible
    // ft / in parts feed the hidden width-in / height-in input (total inches), which the price,
    // the limits, the chips and the cart read as before. A preset or chip sets the hidden total,
    // and the parts follow it.
    document.querySelectorAll('.dim-group--ftin').forEach((group) => {
      const total = group.querySelector('input.dim-input');
      const parts = { ft: group.querySelector('input[data-part="ft"]'), in: group.querySelector('input[data-part="in"]') };
      if (!total || !parts.ft || !parts.in) return;
      let own = false;
      const push = (type) => {
        const inches = Math.round(((parseFloat(parts.ft.value) || 0) * 12 + (parseFloat(parts.in.value) || 0)) * 100) / 100;
        own = true;
        total.value = inches > 0 ? inches : '';
        total.dispatchEvent(new Event(type, { bubbles: true }));
        own = false;
      };
      const pull = () => {
        if (own) return;
        const inches = parseFloat(total.value) || 0;
        const feet = Math.floor(inches / 12);
        parts.ft.value = inches > 0 ? feet : '';
        parts.in.value = inches > 0 ? Math.round((inches - feet * 12) * 100) / 100 : '';
      };
      ['ft', 'in'].forEach((key) => {
        parts[key].addEventListener('input', () => push('input'));
        parts[key].addEventListener('change', () => push('change'));
      });
      total.addEventListener('input', pull);
      total.addEventListener('change', pull);
      pull();
    });

    // Size limits (B2Sign style): nothing shows until a typed size breaks one, then a
    // red "Maximum width is 1200"" under the boxes and the "Max Size" line. The server
    // quote rejects the same sizes (wholesale_product_min_sides / _max_sides).
    const sizeLimits = document.querySelector('.pb-size-summary[data-max-long]');
    if (sizeLimits) {
      const box = sizeLimits.closest('form') || document;
      const dimError = box.querySelector('.pb-dim-error');
      const maxLine = box.querySelector('.pb-max-size');
      const minLine = box.querySelector('.pb-min-size');
      const limit = (key) => parseFloat(sizeLimits.dataset[key]) || 0;
      const dimInput = (name) => box.querySelector(`input[name="${name}-in"]`);
      const fitsMax = (width, height) => {
        const short = limit('maxShort');
        const long = limit('maxLong');
        return !(long && (width > long || height > long)) && !(short && width > short && height > short);
      };
      // Feet-and-inches boxes (_size_unit = ftin, 45 products): notes speak in feet and
      // inches there, not "Sizes are in inches" (Ads visitors on canvas and backlit film,
      // Nov 8 2026, saw that next to a feet box and left).
      const ftin = !!box.querySelector('.dim-group--ftin');
      const len = (inches) => {
        if (!ftin) return `${inches}"`;
        const feet = Math.floor(inches / 12);
        const rest = Math.round((inches - feet * 12) * 100) / 100;
        const text = [feet ? `${feet} ft` : '', rest ? `${rest} in` : ''].filter(Boolean).join(' ') || '0 in';
        return inches >= 12 ? `${text} (${inches}")` : text;
      };
      // Banner buyers think in feet: a "3" in an inch box is likely 3 ft (Ads visitors left
      // on "3 x 33" and "14 x 3"). The too-small note offers that size in inches as a
      // one-tap fix: the small side x 12, or both sides when both look like feet. With
      // feet-and-inches boxes only a size typed in the inch boxes (under 1 ft) gets it.
      const feetFix = (width, height) => {
        const small = (value, key) => value > 0 && value < limit(key);
        const both = ftin ? width > 0 && width < 12 && height > 0 && height < 12 : width <= 20 && height <= 20;
        const fixed = [both || small(width, 'minWidth') ? width * 12 : width, both || small(height, 'minHeight') ? height * 12 : height]
          .map((value) => Math.round(value * 100) / 100);
        return fixed[0] >= limit('minWidth') && fixed[1] >= limit('minHeight') && fitsMax(fixed[0], fixed[1]) ? { fixed, both } : null;
      };
      // The opposite slip on feet-and-inches boxes: inches typed into the feet box. Visitors
      // on styrene and magnets typed 22 and 40 there and got "Maximum width is 8 ft (96")"
      // for 264" and 482" (Visitor Insights, Nov 2026). Offer the feet number as inches when
      // the inch box is empty and that size fits.
      const inchesFix = (width, height) => {
        if (!ftin) return null;
        const feetTyped = (name) => {
          const group = dimInput(name)?.closest('.dim-group--ftin');
          const feet = parseFloat(group?.querySelector('input[data-part="ft"]')?.value) || 0;
          const inches = parseFloat(group?.querySelector('input[data-part="in"]')?.value) || 0;
          return inches ? 0 : feet;
        };
        const short = limit('maxShort');
        const long = limit('maxLong');
        const over = (value, other) => (long && value > long) || (short && value > short && other > short);
        let fixWidth = width;
        let fixHeight = height;
        if (over(width, height)) fixWidth = feetTyped('width');
        if (fixWidth && over(height, fixWidth)) fixHeight = feetTyped('height');
        if (!fixWidth || !fixHeight || (fixWidth === width && fixHeight === height)) return null;
        return fixWidth >= limit('minWidth') && fixHeight >= limit('minHeight') && fitsMax(fixWidth, fixHeight)
          ? { fixed: [fixWidth, fixHeight], inches: true }
          : null;
      };
      let lastNote = '';
      // While typing, "24" passes through "2": the too-small notes wait for the change
      // (leaving the box), so they don't flash red after the first digit.
      const checkSize = (typing) => {
        const width = parseFloat(dimInput('width')?.value) || 0;
        const height = parseFloat(dimInput('height')?.value) || 0;
        const short = limit('maxShort');
        const long = limit('maxLong');
        let error = '';
        let tooBig = false;
        let fix = null;
        if (long && (width > long || height > long)) {
          tooBig = true;
          error = width > long ? `Maximum width is ${len(long)}.` : `Maximum height is ${len(long)}.`;
          if (!typing) fix = inchesFix(width, height);
        } else if (short && width > short && height > short) {
          tooBig = true;
          // Either side may be the short one: say so, or turning the size looks impossible.
          error = short < long
            ? `One side can be at most ${len(short)}; the other up to ${len(long)}.`
            : (width < height ? `Maximum width is ${len(short)}.` : `Maximum height is ${len(short)}.`);
          if (!typing) fix = inchesFix(width, height);
        } else if (typing) {
          // Too small or fine: no note until the change.
        } else if (width > 0 && width < limit('minWidth')) {
          error = ftin ? `Minimum width is ${len(limit('minWidth'))}.` : `Minimum width is ${limit('minWidth')}". Sizes are in inches.`;
          fix = feetFix(width, height);
        } else if (height > 0 && height < limit('minHeight')) {
          error = ftin ? `Minimum height is ${len(limit('minHeight'))}.` : `Minimum height is ${limit('minHeight')}". Sizes are in inches.`;
          fix = feetFix(width, height);
        }
        if (dimError) {
          dimError.textContent = error;
          dimError.hidden = !error;
          // Too big is a dead end on the page: offer the quote form for this product.
          if (tooBig && dimError.dataset.quoteUrl) {
            const help = document.createElement('a');
            help.className = 'pb-dim-help';
            help.href = dimError.dataset.quoteUrl;
            help.textContent = 'Need a bigger size? Ask for a quote';
            dimError.append(' ', help);
          }
          if (fix) {
            const [fixWidth, fixHeight] = fix.fixed;
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'pb-dim-fix';
            if (fix.inches) {
              button.textContent = fixWidth !== width && fixHeight !== height
                ? `Use ${fixWidth} in x ${fixHeight} in`
                : (fixWidth !== width ? `Use ${fixWidth} inches wide` : `Use ${fixHeight} inches high`);
            } else if (ftin) {
              button.textContent = fix.both
                ? `Use ${width} ft x ${height} ft`
                : (fixWidth !== width ? `Use ${width} ft wide` : `Use ${height} ft high`);
            } else {
              button.textContent = fix.both
                ? `Use ${fixWidth}" x ${fixHeight}" (${width} ft x ${height} ft)`
                : (fixWidth !== width ? `Use ${fixWidth}" (${width} ft)` : `Use ${fixHeight}" (${height} ft)`);
            }
            button.addEventListener('click', () => {
              // Both values first, so the change handlers never see one side fixed alone.
              const changed = [['width', fixWidth], ['height', fixHeight]]
                .map(([name, value]) => [dimInput(name), value])
                .filter(([input, value]) => input && parseFloat(input.value) !== value);
              changed.forEach(([input, value]) => { input.value = value; });
              changed.forEach(([input]) => {
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
              });
            });
            dimError.append(' ', button);
          }
        }
        if (maxLine) maxLine.hidden = !tooBig;
        if (minLine) minLine.hidden = !error || tooBig;
        // js/visitor-tracker.js logs the note the visitor saw, with the typed size.
        if (error && error !== lastNote) {
          document.dispatchEvent(new CustomEvent('wholesale:size-note', { detail: { message: error, size: `${width}" x ${height}"` } }));
        }
        lastNote = error;
      };
      box.addEventListener('input', (e) => { if (e.target.matches('.dim-input')) checkSize(true); });
      box.addEventListener('change', (e) => { if (e.target.matches('.dim-input')) checkSize(false); });
      // The boxes open on a default size (wholesale_product_default_size()): run the size
      // handlers once, after the dimension handler below is bound, so every total matches it.
      const defaultWidth = box.querySelector('input[name="width-in"]');
      if (defaultWidth && parseFloat(defaultWidth.value) > 0) {
        setTimeout(() => defaultWidth.dispatchEvent(new Event('change', { bubbles: true })));
      }
    }

    // Custom hole punch panel (single-product.php): shown, and sent, only while
    // "Custom Hole Punch" or "Custom Grommets" is the chosen Holes Punch option.
    document.querySelectorAll('.pb-custom-holes').forEach((panel) => {
      const select = document.getElementById(panel.dataset.for);
      if (!select) return;
      const sync = () => {
        const on = /^Custom (Hole|Grommet)/i.test(select.selectedOptions[0]?.text || '');
        panel.hidden = !on;
        panel.querySelectorAll('fieldset').forEach((fieldset) => { fieldset.disabled = !on; });
      };
      select.addEventListener('change', sync);
      sync();
    });

    // change dimension inputs
    $(document).on("change.productPage", ".product-attibute-box .dimenstion-calculator input", (e) => {
      switch (e.target.name) {
        // Height Feat
        case "height-ft":
          var heigthFt = parseFloat(e.target.value) || 0;
          var heightIn =
            parseFloat(
              $('.product-attibute-box input[name="height-in"]').val()
            ) || 0;
          var widhtFt =
            parseFloat($('.product-attibute-box input[name="width-ft"]').val()) ||
            0;
          var widthIn =
            parseFloat($('.product-attibute-box input[name="width-in"]').val()) ||
            0;
          var totalHeight = heightIn / 12 + heigthFt;
          var totalWidth = widthIn / 12 + widhtFt;
          var totalSqft = totalHeight * totalWidth;

          $('#totalSqft').val(totalSqft.toFixed(2))
          var totalPrice = totalSqft * pricePerSqft;
          $('#totalCost').val(totalPrice.toFixed(2))
          updateDisplay(totalHeight, totalWidth);

          break;

        // Height In.
        case "height-in":
          var heigthFt =
            parseFloat(
              $('.product-attibute-box input[name="height-ft"]').val()
            ) || 0;
          var heightIn = parseFloat(e.target.value) || 0;
          var widhtFt =
            parseFloat($('.product-attibute-box input[name="width-ft"]').val()) ||
            0;
          var widthIn =
            parseFloat($('.product-attibute-box input[name="width-in"]').val()) ||
            0;
          var totalHeight = heightIn / 12 + heigthFt;
          var totalWidth = widthIn / 12 + widhtFt;
          var totalSqft = totalHeight * totalWidth;
          $('#totalSqft').val(totalSqft.toFixed(2))
          var totalPrice = totalSqft * pricePerSqft;
          $('#totalCost').val(totalPrice.toFixed(2))
          updateDisplay(totalHeight, totalWidth);
          break;

        // Width Feat
        case "width-ft":
          var heigthFt =
            parseFloat(
              $('.product-attibute-box input[name="height-ft"]').val()
            ) || 0;
          var heightIn =
            parseFloat(
              $('.product-attibute-box input[name="height-in"]').val()
            ) || 0;
          var widhtFt = parseFloat(e.target.value) || 0;
          var widthIn =
            parseFloat($('.product-attibute-box input[name="width-in"]').val()) ||
            0;
          var totalHeight = heightIn / 12 + heigthFt;
          var totalWidth = widthIn / 12 + widhtFt;
          var totalSqft = totalHeight * totalWidth;
          $('#totalSqft').val(totalSqft.toFixed(2))
          var totalPrice = totalSqft * pricePerSqft;
          $('#totalCost').val(totalPrice.toFixed(2))
          updateDisplay(totalHeight, totalWidth);
          break;

        // Width In
        case "width-in":
          var heigthFt =
            parseFloat(
              $('.product-attibute-box input[name="height-ft"]').val()
            ) || 0;
          var heightIn =
            parseFloat(
              $('.product-attibute-box input[name="height-in"]').val()
            ) || 0;
          var widhtFt =
            parseFloat($('.product-attibute-box input[name="width-ft"]').val()) ||
            0;
          var widthIn = parseFloat(e.target.value) || 0;
          var totalHeight = heightIn / 12 + heigthFt;
          var totalWidth = widthIn / 12 + widhtFt;
          var totalSqft = totalHeight * totalWidth;
          $('#totalSqft').val(totalSqft.toFixed(2))
          var totalPrice = totalSqft * pricePerSqft;
          $('#totalCost').val(totalPrice.toFixed(2))
          updateDisplay(totalHeight, totalWidth);
          break;
        default:
          break;
      }
    });

    // update display on total cost change

    $("#totalCost").change((e) => {
      alert("change");
      let totalPrice = e.target.value;
      // display subtotal
      $(".product-pricing-box .price-subtotal-container  .price-subtotal").text(
        totalPrice.toFixed(2)
      );
      // display total
      $(".product-pricing-box .price-total-container  .price-total").text(
        totalPrice.toFixed(2)
      );
    });

    // letter input change action
    $("#letterInput").on('input',(e) => {
      let updatedText = e.target.value;
      let letterCharacters = e.target.value.replace(/\s/g, "").length;
      //let minInchPrice = $('#select-size option:nth-child(1)').attr('value') || 0;
      $("#letter-output").text(letterCharacters ? updatedText : "ENTER YOUR TEXT");
      let letterPricePerInch = $("#select-height").val()
        ? parseFloat($("#select-height").val())
        : 0;

      let updatedSize = $(`#select-height option[value*='${parseInt(letterPricePerInch)}']`).data('size')

      updateAproxWidth(letterCharacters, updatedSize);

      resetSelect();

      let totalPrice = parseFloat(letterCharacters * letterPricePerInch);
      $("#totalCost").val(totalPrice.toFixed(2))

      updatePrice(totalPrice, totalPrice, 'keyup');


    });

    // action select size change
    $("#select-height").change((e) => {
      let letterCharacters = $("#letterInput").val().replace(/\s/g, "").length;
      let pricePerInch = parseFloat(e.target.value);
      let totalPrice = letterCharacters * pricePerInch;
      let updatedSize = $(`#select-height option[value*=${parseInt(pricePerInch)}]`).data('size')
      $("#totalCost").val(totalPrice.toFixed(2))

      updateAproxWidth(letterCharacters, updatedSize);

      resetSelect();
      return updatePrice(totalPrice, totalPrice, 'height change');
    });



    // update product quantity
    $('#productQuantity').change((e) => {
      if (e.target.value < 1) {
        $(e.target).val(1);

      }
      let currentQty = parseInt($(e.currentTarget).attr('data-current-qty'))
      let qty = parseInt(e.target.value);
      let itemCost = parseFloat($('#totalCost').val()).toFixed(2);
      let singleCost = (itemCost / currentQty) || 0;
      let totalPrice = qty * singleCost;
      $(e.currentTarget).attr('data-current-qty', qty);
      $('#totalCost').val(totalPrice)
      updatePrice(totalPrice, totalPrice, 'qty change');
    })

    // select difault value in size

    let currentSize = $("#select-height option:first-child").attr("data-size");
    $("#select-height").attr("data-selected-size", currentSize);

    $("#select-height option").click((e) => {
      let size = $(e.target).attr("data-size");
      $(e.target).parent().attr("data-selected-size", size);
    });

    // action select font change
    $("#select-font").change((e) => {
      let value = e.target.value;
      let valueArray = value.split(" ");
      if (valueArray[valueArray.length - 1] == "bold") {
        $("#letter-output").css("font-weight", "bold");
        return $("#letter-output").css("font-family", valueArray[0]);
      } else {
        $("#letter-output").css("font-weight", "700");
      }
      $("#letter-output").css("font-family", e.target.value);
      $(e.target).css("font-family", e.target.value);
      // let fw = $('#select-font option[value="'+e.target.value+'"]').attr('data-fw')
      // if(fw) {
      //   alert(fw)

      // }
    });

    $("#select-font option").each((e) => {
      let fontName = $("#select-font option:nth-child(" + (e + 1) + ")").attr(
        "value"
      );
      $("#select-font option:nth-child(" + (e + 1) + ")").css(
        "font-family",
        fontName
      );
    });

    // action select color change
    $("#select-face").change((e) => {
      let isSameReturnColor = $("#returnColorSame").val();
      if (e.target.value.split("/")[2] === "dual-color-white") {
        $("#letter-output").removeClass("dual-color-black");

        return $("#letter-output").addClass("dual-color-white");
      } else if (e.target.value.split("/")[2] === "dual-color-black") {
        $("#letter-output").removeClass("dual-color-white");
        return $("#letter-output").addClass("dual-color-black");
      } else {
        $("#letter-output").removeClass("dual-color-black");
        $("#letter-output").removeClass("dual-color-white");
      }

      if (isSameReturnColor == "on") {
        $("#letter-output").css({
          "text-shadow": "3px 3px 2px " + e.target.value.split("/")[2],
        });
        let colorName = $(
          '#select-face option[value="' + e.target.value + '"'
        ).text();
        $(".return-option-name").text(colorName);
      }

      $("#letter-output").css("color", e.target.value.split("/")[2]);
    });


    // action select font
    // $('#select-font option').each(e => {
    //   let color = $('#select-color option:nth-child('+(e+1)+')').attr('value');
    //   $('#select-color option:nth-child('+(e+1)+')').css('color', color);
    //   $(e.target).css('font-family',e.target.value);
    // })

    // action change trimcap

    $("#select-trimcap").change((e) => {
      let trimCapColor = e.target.value;

      $("#letter-output").css({
        "text-stroke": "2px " + trimCapColor,
        "-webkit-text-stroke": "2px " + trimCapColor,
      });
    });

    let trimcapColor = $("#trimcapColor").val();
    if (trimcapColor) {
      $("#letter-output").css({
        "text-stroke": "2px " + trimcapColor,
        "-webkit-text-stroke": "2px " + trimcapColor,
      });
    }

    // action change return color
    $("#select-return").change((e) => {
      let returnColor = e.target.value;

      $("#letter-output").css({
        "text-shadow": "3px 3px 2px " + returnColor,
      });
    });

    // select custom height width
    $('.size-width-inch,.size-height-inch').on('change', (e) => {
      let pricePerSqft = parseFloat(
        $(".product-attibute-box #pricePerSqft").val() || 0
      );

      let widthInch = parseFloat($('.size-width-inch').val() || 0)
      let widthFeat = widthInch / 12

      let heightInch = parseFloat($('.size-height-inch').val() || 0)
      let heightFeat = heightInch / 12

      let totalSqft = heightFeat * widthFeat
      // $('#totalSqft').val(totalSqft)
      // let totalPrice = parseFloat(totalSqft * pricePerSqft)
      updateDisplay(heightFeat, widthFeat);
      $('.size-width-inch').val(widthInch)
      $('.size-height-inch').val(heightInch)

      let dimentionText = `<span class="custom-dimenstion-text">${widthInch}" x ${heightInch}" = ${totalSqft.toFixed(
        2
      )} ft<sup>2</sup></span> `;
      let customDimensionText = $('.custom-dimenstion-text') || 0
      if (customDimensionText.length > 0) {
        dimentionText = `${widthInch}" x ${heightInch}" = ${totalSqft.toFixed(
          2
        )} ft<sup>2</sup>`;
        $('.custom-dimenstion-text').html(dimentionText)

      } else {
        $('.select-runner-height').parent().append(dimentionText);

      }
    })



    // product thumbnail on click gallery click

    // The frame keeps its size (css/style.css, .product-image-zoom), so a photo of
    // another shape is fitted inside it rather than spilling over the page.
    $(".gallery-image-item").click((e) => {
      let imageSrc = $(e.currentTarget).attr("data-image");
      $(".thumbnail-container .single-product-thumbnail").removeAttr('srcset')
      $(".thumbnail-container .single-product-thumbnail").attr("src", imageSrc);
      // The main photo takes the thumbnail's description (data/image-alts.php).
      $(".thumbnail-container .single-product-thumbnail").attr("alt", $(e.currentTarget).find("img").attr("alt") || "");
      $(".product-image-zoom").attr("data-zoom-src", imageSrc);
    });

    // zoom the main product image on hover (mouse only, not touch)

    if (window.matchMedia("(hover: hover) and (pointer: fine)").matches) {
      $(".product-image-zoom")
        .on("mouseenter", function () {
          let zoomSrc = $(this).attr("data-zoom-src");
          if (!zoomSrc) return;
          $(this).find(".product-image-zoom__lens").css("background-image", `url("${zoomSrc}")`);
          $(this).addClass("is-zooming");
        })
        .on("mousemove", function (e) {
          let rect = this.getBoundingClientRect();
          let x = ((e.clientX - rect.left) / rect.width) * 100;
          let y = ((e.clientY - rect.top) / rect.height) * 100;
          $(this).find(".product-image-zoom__lens").css("background-position", `${x}% ${y}%`);
        })
        .on("mouseleave", function () {
          $(this).removeClass("is-zooming");
        });
    }

    // Click or tap the main photo to see it full size. Visitors clicked it again and
    // again (rage clicks in Visitor Insights): the hover zoom does nothing on a click
    // and phones had no way to enlarge it at all. Styles: css/ui-polish.css 9h.
    $(".product-image-zoom")
      .attr({ role: "button", tabindex: "0", "aria-label": "View larger photo" })
      .on("click keydown", function (e) {
        if (e.type === "keydown" && e.key !== "Enter" && e.key !== " ") return;
        let src = $(this).attr("data-zoom-src") || $(this).find("img").attr("src");
        if (!src) return;
        e.preventDefault();
        let opener = this;
        let $box = $('<div class="pd-lightbox" role="dialog" aria-modal="true" aria-label="Product photo"><button type="button" class="pd-lightbox__close" aria-label="Close">&times;</button><img alt=""></div>');
        $box.find("img").attr({ src: src, alt: $(this).find("img").attr("alt") || "" });
        let close = () => {
          $box.remove();
          $(document).off("keydown.pdLightbox");
          document.documentElement.classList.remove("pd-lightbox-open");
          opener.focus();
        };
        $box.on("click", close);
        $(document).on("keydown.pdLightbox", (ev) => { if (ev.key === "Escape") close(); });
        $("body").append($box);
        document.documentElement.classList.add("pd-lightbox-open");
        $box.find(".pd-lightbox__close").trigger("focus");
      });

    // handle product categories click

    $(".category-selector").click((e) => {
      $(e.currentTarget).addClass("active");
      $(e.currentTarget).siblings().removeClass("active");
      let category = $(e.currentTarget).attr("data-cat");

      $(".product-box-container .product-box").fadeOut("fast");
      $(
        ".product-box-container .product-box[data-product-category=" +
        category +
        "]"
      ).fadeIn("slow");

      if (category == "all") {
        $(".product-box-container .product-box").fadeIn("fast");
      }
    });

    let windowHash = (window.location.hash).replace('#', '');
    if (windowHash == 'channel-letters') {
      $('#channelLetterFilterBtn').trigger('click');
    } else if (windowHash == 'adhesive-products') {
      $('#adhesiveLetterFilterBtn').trigger('click');

    }

    // Header, menus and the cart badge: first run only (a product swap keeps the header).
    if (first) {
    const updateStickyHeader = () => {
      $(".main-header").toggleClass("is-scrolled", window.scrollY > 12);
    };

    updateStickyHeader();
    $(window).on("scroll", updateStickyHeader);

    // Slide-in menu (tablet and phone). The open state lives on the header as
    // .mobile-menu-open; header.css slides the panel in and dims the page.
    const menuTrigger = $(".mobile-menu-trigger");
    const isMobileMenuOpen = () => $(".main-header").hasClass("mobile-menu-open");

    const setMobileMenu = (open) => {
      const wasOpen = isMobileMenuOpen();
      $(".main-header").toggleClass("mobile-menu-open", open);
      menuTrigger.attr("aria-expanded", String(open));
      document.documentElement.classList.toggle("sh-lock", open);
      if (open) {
        closeMegaMenu();
        // Focus moves after the slide starts so the panel doesn't jump.
        setTimeout(() => $(".mobile-menu-close").trigger("focus"), 50);
      } else if (wasOpen) {
        menuTrigger.trigger("focus");
      }
    };
    const closeMobileMenu = () => setMobileMenu(false);

    $(".mobile-menu-close, .mobile-menu-container .menu-item a, .mobile-menu-cta").click(() => {
      closeMobileMenu();
    });

    menuTrigger.click((e) => {
      e.stopPropagation();
      setMobileMenu(!isMobileMenuOpen());
    });

    // Clicking the dimmed page (the header's ::after overlay) closes the menu.
    $(".main-header").on("click", (e) => {
      if (e.target === e.currentTarget) closeMobileMenu();
    });

    // Keep Tab inside the open menu.
    $(".mobile-menu-container").on("keydown", (e) => {
      if (e.key !== "Tab") return;
      const focusable = $(e.currentTarget).find("a[href], button:not([disabled])").filter(":visible");
      if (!focusable.length) return;
      const first = focusable.get(0);
      const last = focusable.get(focusable.length - 1);
      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    });

    // The slide-in menu doesn't exist on desktop widths.
    const desktopQuery = window.matchMedia("(min-width: 1200px)");
    const onDesktopChange = () => {
      if (desktopQuery.matches && isMobileMenuOpen()) closeMobileMenu();
    };
    if (desktopQuery.addEventListener) desktopQuery.addEventListener("change", onDesktopChange);
    else if (desktopQuery.addListener) desktopQuery.addListener(onDesktopChange);

    // Cart badge. The count comes from a cookie the server keeps in sync, because
    // cached pages can't include it.
    const cartCount = (() => {
      const match = document.cookie.match(/(?:^|;\s*)sso_cart_count=(\d+)/);
      return match ? parseInt(match[1], 10) : 0;
    })();
    $("[data-cart-count]").each((i, el) => {
      el.textContent = cartCount > 99 ? "99+" : String(cartCount);
      el.hidden = cartCount < 1;
    });
    $("[data-cart-link]").attr("aria-label", cartCount > 0 ? `Cart, ${cartCount} ${cartCount === 1 ? "item" : "items"}` : "Cart");

    $('.category-filter-toggler').click(e => {
      const toggler = $(e.currentTarget);
      const menu = toggler.siblings('.menu-filter-menu-container');
      const isExpanded = toggler.attr('aria-expanded') === 'true';

      toggler.attr('aria-expanded', String(!isExpanded));
      menu.stop(true, true).slideToggle('fast');
      toggler.toggleClass('is-open', !isExpanded);
    })

    $('#allProductsBtn').click( e => {
      e.stopPropagation();
      const button = $(e.currentTarget);
      const menu = $('#megaMenu');
      const isOpen = menu.is(':visible');

      menu.stop(true, true).toggle(!isOpen);
      button.attr('aria-expanded', String(!isOpen));
      $('.main-header').toggleClass('mega-menu-open', !isOpen);
      document.documentElement.classList.toggle(
        "sh-lock",
        (!isOpen && window.matchMedia("(max-width: 767.98px)").matches) || isMobileMenuOpen()
      );
      if (!isOpen) {
        sizeMegaMenu();
        if (window.matchMedia("(max-width: 767.98px)").matches) {
          $('.mega-menu-close').trigger('focus');
        }
      }
    })

    // Phone bottom bar opens the same product menu (the header's button is hidden there).
    $('.cl-mobile-bar-products').click(e => {
      e.stopPropagation();
      $('#allProductsBtn').trigger('click');
    });

    // Close the product menu from outside clicks and Escape; Escape also closes the slide-in menu.
    $(document).on('click', (e) => {
      if ($('.main-header').hasClass('mega-menu-open') && !$(e.target).closest('#megaMenu, #allProductsBtn').length) {
        closeMegaMenu();
      }
    });
    $(document).on('keydown', (e) => {
      if (e.key !== 'Escape') return;
      if ($('.main-header').hasClass('mega-menu-open')) {
        closeMegaMenu();
        $('#allProductsBtn').trigger('focus');
      }
      if (isMobileMenuOpen()) closeMobileMenu();
    });

    // Fit the menu into the space left below the sticky header so it scrolls on small screens.
    function sizeMegaMenu() {
      const menu = document.getElementById('megaMenu');
      if (!menu || menu.offsetParent === null) return;
      if (window.matchMedia("(max-width: 767.98px)").matches) {
        menu.style.removeProperty('--mega-menu-max-height');
        return;
      }
      const available = window.innerHeight - menu.getBoundingClientRect().top - 12;
      menu.style.setProperty('--mega-menu-max-height', Math.max(available, 200) + 'px');
    }

    $(window).on('resize orientationchange', sizeMegaMenu);

    // The review modal sits inside the footer's scroll-reveal block, whose transform
    // pins position:fixed to the footer. Hoist it to <body> so it opens over the viewport.
    const feedbackModalEl = document.getElementById('feedbackModal');
    if (feedbackModalEl && feedbackModalEl.parentElement !== document.body) {
      document.body.appendChild(feedbackModalEl);
    }

    function closeMegaMenu() {
      $('#megaMenu').stop(true, true).hide();
      $('#allProductsBtn').attr('aria-expanded', 'false');
      $('.main-header').removeClass('mega-menu-open');
      document.documentElement.classList.toggle("sh-lock", isMobileMenuOpen());
    }

    $('.mega-menu-close').click(() => {
      closeMegaMenu();
      $('#allProductsBtn').trigger('focus');
    });

    $('.mega-menu-backdrop').click(closeMegaMenu);
    }

    // Product options (channel letter color pickers, letter preview, option info) and the cart page.
    // custom select

    $(".custom-select-container").click((e) => {
      $(e.currentTarget).children(".custom-select").toggle();
    });

    $(".custom-select li").click((e) => {
      let currentSlectedText = $(e.currentTarget).text();
      let selectedValue = $(e.currentTarget).data("value");
      let selectId = $(e.currentTarget)
        .parents(".custom-select-container")
        .attr("id")
        .split("-")[2];
      $(e.currentTarget)
        .parents(".custom-select-container")
        .attr("data-value", selectedValue);

      $(e.currentTarget)
        .parent()
        .siblings(".color-sample")
        .css("background-color", selectedValue.split("/")[2] || selectedValue);
      $(e.currentTarget)
        .parent()
        .siblings(".custom-select-selected")
        .text(currentSlectedText);
      $("#select-" + selectId + "").val(selectedValue);
      $("#select-" + selectId + "").trigger("change");
    });

    $(".custom-select li:first-child").trigger("click");
    $(".custom-select").hide();

    $("#select-face").parents(".select-row").hide();
    $("#select-trimcap").parents(".select-row").hide();
    $("#select-return").parents(".select-row").hide();

    // The big "Enter Your Text" preview looks like the place to type; visitors
    // tapped it repeatedly. Send them to the real text box.
    $('#letter-output').css('cursor', 'text').on('click', () => {
      $('#letterInput').trigger('focus');
    });

    $('.option-info-button').click(e => {
      $(e.currentTarget).siblings('.option-info-content').toggle();
    })


    $('.cart-details-toggler').click(e => {
      let currentTarget = e.currentTarget
      $(currentTarget).parent().siblings('.cart-details-container').toggle();
    })



    //checkout page data 
    $('#sameShippingAddress').change(e => {
      let isChecked = $(e.currentTarget).is(':checked');
      if (isChecked) {
        $('.shipping-address-container').hide()
        $('.shipping-address-container input').removeAttr('required');
      } else {
        $('.shipping-address-container').show()
        $('.shipping-address-container input:not(#shippingAddress2)').attr('required', 'true')

      }
    })

    // $('#shippingCost').on('change', function(e) {
    //   let grandTotalInput = $('#grandTotal');
    //   let prevSCcost = parseFloat(grandTotalInput.attr('data-sc')).toFixed(2)
    //   let grandTotal = $('#grandTotal').val();
    //   let subTotal = $('#subTotal').val();


    //   if(prevSCcost > 0) {
    //     grandTotalInput.attr('data-sc',"0")
    //     grandTotalInput.val(subTotal + $(e.currentTarget).val())
    //   }else {
    //     grandTotalInput.val(subTotal + $(e.currentTarget).val())

    //   }


    // })
    let updateGrandTotal = (subTotal, shippingCost) => {
    let totalTax = parseFloat($('#totalTax').val()) || 0;
    let grandTotal = parseFloat(subTotal + shippingCost + totalTax).toFixed(2);
    $('#grandTotal').val(grandTotal);
    return parseFloat(grandTotal);
    }
    // shipping cost radio change
    $('.shipping-radio[name="shipping_method"]').change(function () {
    let newShippingCost = parseFloat($(this).val());
    let checkoutSubTotal = parseFloat($('#subTotal').val()) || 0;
    let productTurnaround = parseInt($('#productTurnaround').val()) || 0;

    // Options are listed standard -> overnight, so the delivery offset follows the option position
    // rather than hard-coded prices (which admins can change in settings).
    let shippingDayOffsets = [5, 3, 2, 0];
    let shippingIndex = $('.shipping-radio[name="shipping_method"]').index(this);
    let dayOffset = shippingDayOffsets[Math.min(Math.max(shippingIndex, 0), shippingDayOffsets.length - 1)];
    let deliveryDate = formatDateWithAddedDays(productTurnaround + dayOffset);
    $('#estimateDeliveryText').text(`Order in the next 12 hrs and your order will ship by ${deliveryDate}`);
    $('#estimateDeliveryTime').val(deliveryDate);

    let totalTax = parseFloat($('#totalTax').val()) || 0;
    let grandTotal = parseFloat(checkoutSubTotal + newShippingCost + totalTax);
    $('.grand-total-holder').text('$' + numberWithCommas(grandTotal.toFixed(2)))
    $('.shipping-cost-holder').text('$' + newShippingCost.toFixed(2))
    $('#shippingCost').val(newShippingCost.toFixed(2))
    $('#grandTotal').val(grandTotal.toFixed(2))
    })


    $('.add-to-cart-btn').click(e => {
      let minSqft = parseFloat($('#minSqft').val());
      let totalSqft = parseFloat($('#totalSqft').val());
      let costBeforeDiscount = parseFloat($('#totalCost').val()).toFixed(2);
      let discountCost = ((costBeforeDiscount * discountPercent) / 100).toFixed(2)
      let costAfterDiscount = costBeforeDiscount - discountCost
      if (promoPercent) {
        costAfterDiscount = costAfterDiscount - ((costAfterDiscount * promoPercent) / 100)
      }
      $('#totalCost').val(costAfterDiscount)
      if (costBeforeDiscount <= 0) {
        e.preventDefault();
        // No sign text yet: take the visitor to the text box instead of
        // leaving a dead, disabled button.
        const letterInput = document.getElementById('letterInput');
        if (letterInput && !letterInput.value.trim()) {
          letterInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
          letterInput.focus({ preventScroll: true });
          letterInput.reportValidity();
          return;
        }
        $(e.currentTarget).prop('disabled', true);
        return;
      }

    })

    $('.product-attibute-box').each(e => {
      let boxSelector = '.product-attibute-box:nth-child(' + e + ')';
      let boxChidrens = $(boxSelector).children('.row').length
      if (boxChidrens == 0) {
        $(boxSelector).remove();
      }
    })

    //     $(document).on('click','#turnaroundNextDay', e => {
    //       let turnaroundOption = e.target.value;
    //       let productTotalCost = parseFloat($('#totalCost').val());
    // if(turnaroundOption == 'next_day') {
    //         updatePrice(productTotalCost /2 , productTotalCost / 2);

    //       }
    //     })

    $(document).on('change.productPage', 'input[name="turnaround_option"]', e => {
      let turnaroundOption = e.target.value;
      let productTotalCost = parseFloat($('#totalCost').val());

      if (turnaroundOption == 'next_day') {
        let oldTurnaroundCost = parseFloat($('#turnaroundCost').val())
        $('#turnaroundCost').val(0)
        updatePrice(productTotalCost - oldTurnaroundCost, productTotalCost - oldTurnaroundCost,"(turnaroundOption == 'next_day')");
      }

      if (turnaroundOption == 'same_day') {
        $('#turnaroundCost').val(productTotalCost)
        updatePrice(productTotalCost, productTotalCost,"(turnaroundOption == 'same_day')");

      }
    })

    // Option links open the product with those options chosen:
    //   ?display-size=10ft&of-sides=double-sided&turnaround_option=days_3&quantity=2
    // A select (#select-<name> or name="<name>") takes the option whose name matches however
    // the link writes it ("10ft", "10-ft", "Double Sided", "double-sided", "24x18" for 24" x 18",
    // or the stored "141.17/10ft"); a radio group takes the matching choice; quantity sets the
    // number box. Selects first, then radios and quantity (an option change resets those).
    // Unknown keys or values change nothing; hidden and price fields are never set from a link.
    // ?size=WxH is a size link (single-product.php); ?graphic_only=true etc. picks a product of
    // the group (inc/product-group-swap.php). Native change events, so the live price (server
    // quote), the option chips and the advanced finishing box follow.
    const productForm = document.querySelector('.product-details form[data-noun]');
    const params = new URLSearchParams(window.location.search);
    if (productForm && [...params.keys()].length) {
      const squash = (text) => String(text == null ? '' : text).toLowerCase().replace(/[^a-z0-9]+/g, '');
      const optionName = (value) => String(value).replace(/^-?[\d.]+\//, '');
      const chooseSelect = (select, wanted) => {
        const options = [...select.options].filter((o) => !o.disabled);
        const option = options.find((o) => o.value === wanted)
          || options.find((o) => squash(o.textContent) === squash(wanted))
          || options.find((o) => squash(optionName(o.value)) === squash(wanted))
          || options.find((o) => squash(o.value) === squash(wanted));
        if (!option || option.selected) return;
        // Channel letter color pickers show a list over a hidden select; their click sets both.
        const picker = document.querySelector(`#custom-select-${CSS.escape(select.id.replace(/^select-/, ''))} li[data-value="${CSS.escape(option.value)}"]`);
        if (picker) {
          picker.click();
        } else {
          select.value = option.value;
          select.dispatchEvent(new Event('change', { bubbles: true }));
        }
        const advanced = select.closest('details.pb-advanced-finishing');
        if (advanced && select.selectedIndex > 0) advanced.open = true;
      };
      const selects = [...productForm.querySelectorAll('select')];
      const later = [];
      for (const [key, value] of params) {
        if (!value) continue;
        if (key === 'size' && /^\d+(\.\d+)?x\d+(\.\d+)?$/.test(value)) continue;
        if (document.querySelector(`.product-group-item-link[data-group-key="${CSS.escape(key)}"]`)) continue;
        const select = productForm.querySelector(`select#select-${CSS.escape(key)}`)
          || selects.find((s) => s.name === key)
          || selects.find((s) => squash(s.name) === squash(key) || squash(s.id.replace(/^select-/, '')) === squash(key));
        if (select) {
          chooseSelect(select, value);
        } else {
          later.push([key, value]);
        }
      }
      for (const [key, value] of later) {
        if (/^(quantity|qty)$/i.test(key)) {
          const qty = productForm.querySelector('#productQuantity');
          const count = Math.min(1000, Math.max(1, parseInt(value, 10) || 0));
          if (qty && count && String(count) !== qty.value) {
            qty.value = count;
            qty.dispatchEvent(new Event('change', { bubbles: true }));
          }
          continue;
        }
        const radios = [...productForm.querySelectorAll('input[type="radio"]')].filter((r) => r.name === key || squash(r.name) === squash(key));
        const radio = radios.find((r) => r.value === value)
          || radios.find((r) => squash(r.value) === squash(value))
          || radios.find((r) => r.labels && r.labels[0] && squash(r.labels[0].textContent) === squash(value));
        if (radio && !radio.disabled && !radio.checked) {
          radio.checked = true;
          radio.dispatchEvent(new Event('change', { bubbles: true }));
        }
      }
    }
  };
  $(document).ready(() => pageInit(true));
  document.addEventListener('wholesale:product-swapped', () => pageInit(false));
})(jQuery);

// Footer review slider (rendered by inc/reviews.php on every page).
(() => {
  const init = () => {
    document.querySelectorAll('[data-review-slider]').forEach((slider) => {
      const track = slider.querySelector('[data-review-track]');
      const prev = slider.querySelector('[data-review-prev]');
      const next = slider.querySelector('[data-review-next]');
      const dotsWrap = slider.querySelector('[data-review-dots]');
      if (!track) return;

      const cards = [...track.children];
      const step = () => (cards[0] ? cards[0].getBoundingClientRect().width + parseFloat(getComputedStyle(track).columnGap || 0) : track.clientWidth);
      const perView = () => Math.max(1, Math.round(track.clientWidth / step()));
      const pageCount = () => Math.max(1, Math.ceil(cards.length / perView()));
      const maxScroll = () => track.scrollWidth - track.clientWidth;
      const currentPage = () => {
        // The last page is usually partial, so its scroll position is clamped to the end.
        if (track.scrollLeft >= maxScroll() - 4) return pageCount() - 1;
        return Math.round(track.scrollLeft / (step() * perView()));
      };
      const goTo = (page) => {
        const pages = pageCount();
        const target = ((page % pages) + pages) % pages;
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        track.scrollTo({ left: Math.min(target * step() * perView(), maxScroll()), behavior: reduceMotion ? 'auto' : 'smooth' });
      };

      const renderDots = () => {
        if (!dotsWrap) return;
        const pages = pageCount();
        dotsWrap.innerHTML = '';
        slider.classList.toggle('is-static', pages < 2);
        if (pages < 2) return;
        for (let i = 0; i < pages; i++) {
          const dot = document.createElement('button');
          dot.type = 'button';
          dot.className = 'review-dot';
          dot.tabIndex = -1;
          dot.addEventListener('click', () => goTo(i));
          dotsWrap.appendChild(dot);
        }
        updateDots();
      };

      const updateDots = () => {
        if (!dotsWrap) return;
        const active = Math.min(currentPage(), pageCount() - 1);
        [...dotsWrap.children].forEach((dot, i) => dot.classList.toggle('is-active', i === active));
      };

      prev && prev.addEventListener('click', () => goTo(currentPage() - 1));
      next && next.addEventListener('click', () => goTo(currentPage() + 1));
      track.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowRight') { event.preventDefault(); goTo(currentPage() + 1); }
        if (event.key === 'ArrowLeft') { event.preventDefault(); goTo(currentPage() - 1); }
      });

      let scrollTimer;
      track.addEventListener('scroll', () => {
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(updateDots, 80);
      }, { passive: true });

      let resizeTimer;
      window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(renderDots, 150);
      });

      renderDots();
    });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

// Live price from the server (inc/pricing.php) so the product page shows exactly what the cart charges.
(() => {
  // Runs again after a product group swap (js/product-group-swap.js); each run's document
  // listener ends before the next run binds its own.
  let scope = null;
  const init = () => {
    if (scope) scope.abort();
    scope = new AbortController();
    const productIdInput = document.querySelector('form input[name="product_id"]');
    const pricingBox = document.querySelector('.product-pricing-box');
    if (!productIdInput || !pricingBox || !window.wholesaleShop) return;

    const form = productIdInput.closest('form');
    const money = (value) => Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const addButtons = form.querySelectorAll('.add-to-cart-btn');

    let message = pricingBox.querySelector('.price-quote-message');
    if (!message) {
      message = document.createElement('p');
      message.className = 'price-quote-message';
      message.setAttribute('role', 'status');
      message.hidden = true;
      pricingBox.querySelector('.total-container')?.after(message);
    }

    // B2Sign-style size box: "min charge $X" shows only while that minimum is what the
    // customer pays, and before a size is typed the price is that minimum charge.
    const sizeSummary = form.querySelector('.pb-size-summary');
    const minChargeText = sizeSummary && sizeSummary.querySelector('.pb-min-charge');
    const sizeIsEmpty = () => !!sizeSummary && ['width-in', 'height-in'].some((name) => !(parseFloat(form.querySelector(`input[name="${name}"]`)?.value) > 0));
    const showPrices = (everyday, saving, total) => {
      pricingBox.querySelectorAll('.price-subtotal').forEach((el) => { el.textContent = money(everyday); });
      pricingBox.querySelectorAll('.price-saving').forEach((el) => { el.textContent = money(saving); });
      pricingBox.querySelectorAll('.price-total').forEach((el) => { el.textContent = money(total); });
    };

    // Add To Cart while a new price is on its way (a size typed a moment ago) waits for it,
    // then adds. The cart checks the price sent against its own, and an early tap sent the
    // old estimate: "The price ... was updated to $39.20" right after adding. Registered
    // before the size guard, which needs that quote's answer too.
    let addAfterQuote = null;
    let quoteWaitTimer;
    const settleQuote = () => {
      delete form.dataset.quoting;
      clearTimeout(quoteWaitTimer);
      const button = addAfterQuote;
      addAfterQuote = null;
      if (button) {
        button.classList.remove('is-adding');
        button.click();
      }
    };
    form.addEventListener('click', (event) => {
      const button = event.target.closest('.add-to-cart-btn');
      if (!button || !form.dataset.quoting) return;
      event.preventDefault();
      event.stopImmediatePropagation();
      if (addAfterQuote) return;
      addAfterQuote = button;
      button.classList.add('is-adding');
      // A slow server doesn't hold the add for long: the cart prices it either way.
      quoteWaitTimer = setTimeout(settleQuote, 6000);
    }, true);

    // Size guard: Add To Cart with no size, or a size outside the limits, goes back to the
    // size box with a red note under it instead of adding anything (runs before the cart
    // handlers, in the capture phase).
    if (sizeSummary) {
      form.addEventListener('click', (event) => {
        if (!event.target.closest('.add-to-cart-btn')) return;
        const empty = sizeIsEmpty();
        if (!empty && !form.dataset.sizeError) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        const dimError = form.querySelector('.pb-dim-error');
        const blank = ['width-in', 'height-in'].map((name) => form.querySelector(`input[name="${name}"]`)).find((input) => input && !(parseFloat(input.value) > 0));
        if (dimError && (empty || !dimError.textContent)) {
          dimError.textContent = empty ? `Please enter the ${blank && blank.name === 'height-in' ? 'height' : 'width'} of your ${form.dataset.noun || 'product'}.` : form.dataset.sizeError;
          dimError.hidden = false;
          const typed = ['width-in', 'height-in'].map((name) => parseFloat(form.querySelector(`input[name="${name}"]`)?.value) || 0);
          document.dispatchEvent(new CustomEvent('wholesale:size-note', { detail: { message: dimError.textContent, size: `${typed[0]}" x ${typed[1]}"` } }));
        }
        let target = blank || form.querySelector('input[name="width-in"]');
        // Feet-and-inches boxes: the total is hidden, so the feet part takes the focus.
        if (target && target.type === 'hidden') target = target.closest('.dim-group')?.querySelector('input[data-part="ft"]') || target;
        target?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        target?.focus({ preventScroll: true });
      }, true);
    }

    let turnaroundRow = pricingBox.querySelector('.price-turnaround-row');
    if (!turnaroundRow) {
      turnaroundRow = document.createElement('div');
      turnaroundRow.className = 'row price-turnaround-row';
      turnaroundRow.hidden = true;
      turnaroundRow.innerHTML = '<div class="col-6"><span class="fs-6">Same-day production</span></div><div class="col-6 price-subtotal-container"><span class="fs-6 d-block">+$<span class="price-turnaround"></span></span></div>';
      const anchor = pricingBox.querySelector('.totalSaving-container') || pricingBox.querySelector('.subtotal-container');
      anchor?.after(turnaroundRow);
    }

    // Prices the server has already worked out: this page's earlier quotes, plus the
    // "variants" each quote brings back (every other choice of every option, and one more /
    // one less in quantity, priced in the same request by inc/pricing.php). A choice whose
    // price is here shows it at once instead of waiting for the server. Kept 3 minutes, as a
    // dated sale or the same-day cut-off can change a price.
    const quoteCache = new Map();
    const CACHE_MS = 3 * 60 * 1000;
    // Fields the price doesn't depend on (filled in by the page itself, or free text).
    const unpriced = new Set(['action', 'variants', 'custom-artwork', 'total_cost', 'total_sqft', 'turnaround_cost', 'job_name']);
    const formEntries = () => [...new FormData(form)].filter(([, value]) => typeof value === 'string');
    const cacheKey = (entries) => entries.filter(([name]) => !unpriced.has(name)).map(([name, value]) => encodeURIComponent(name) + '=' + encodeURIComponent(value)).join('&');
    const cacheGet = (key) => {
      const hit = quoteCache.get(key);
      return hit && Date.now() - hit.at < CACHE_MS ? hit : null;
    };
    const cachePut = (key, quote, primed) => {
      const old = cacheGet(key);
      if (old && old.primed && !primed) return; // Keep the entry that knows its own variants.
      if (quoteCache.size > 500) quoteCache.clear();
      quoteCache.set(key, { quote, primed, at: Date.now() });
    };
    // Every other value of each choice on the page (dropdowns, radio choices) and one more /
    // one less in quantity: the server prices each with the rest of the form unchanged.
    const variantList = (entries) => {
      const sent = new Map(entries);
      const varied = (name) => !!name && sent.has(name) && !unpriced.has(name) && !name.endsWith('[]');
      const list = [];
      form.querySelectorAll('select[name]').forEach((select) => {
        if (select.disabled || select.multiple || !varied(select.name)) return;
        [...select.options].forEach((option) => {
          if (!option.disabled && option.value !== sent.get(select.name)) list.push([select.name, option.value]);
        });
      });
      form.querySelectorAll('input[type="radio"][name]:not(:checked):not(:disabled)').forEach((radio) => {
        if (varied(radio.name)) list.push([radio.name, radio.value]);
      });
      const quantity = parseInt(sent.get('product_quantity'), 10);
      if (quantity > 0) {
        list.push(['product_quantity', String(quantity + 1)]);
        if (quantity > 1) list.push(['product_quantity', String(quantity - 1)]);
      }
      return list.slice(0, 80);
    };

    // Shows one quote from the server (fresh or from the cache above).
    const applyQuote = (quote) => {
      // Only the size is outside the product's limits: like B2Sign, show its price and
      // keep Add To Cart clickable (the size guard and the cart still refuse it).
      form.dataset.sizeError = quote.size_error ? quote.error : '';
      if (!quote.ok && quote.size_error) {
        if (minChargeText) minChargeText.hidden = !(quote.minimum_order > 0);
        message.hidden = true;
        showPrices('everyday' in quote ? quote.everyday : quote.subtotal, 'promo_discount' in quote ? quote.promo_discount : quote.discount, quote.total);
        turnaroundRow.hidden = !(quote.turnaround > 0);
        addButtons.forEach((button) => { button.disabled = false; });
        return;
      }

      if (!quote.ok) {
        // No sign text yet (every channel letter page opens this way):
        // a prompt rather than an error, and Add To Cart stays enabled so
        // the browser's required-field check takes the visitor to the box.
        const letterInput = form.querySelector('#letterInput');
        const needsText = letterInput && !letterInput.value.trim();
        message.textContent = needsText ? 'Type your sign text above to see your exact price.' : quote.error;
        message.classList.toggle('is-hint', !!needsText);
        message.hidden = false;
        addButtons.forEach((button) => { button.disabled = !needsText; });
        return;
      }

      if (minChargeText) minChargeText.hidden = !(quote.minimum_order > 0);

      message.hidden = true;
      message.classList.remove('is-hint');
      // Shown only during a dated sale: the everyday price and what the sale takes off it.
      showPrices('everyday' in quote ? quote.everyday : quote.subtotal, 'promo_discount' in quote ? quote.promo_discount : quote.discount, quote.total);
      turnaroundRow.hidden = !(quote.turnaround > 0);
      turnaroundRow.querySelector('.price-turnaround').textContent = money(quote.turnaround);
      turnaroundRow.querySelector('.fs-6').textContent = quote.turnaround_label ? quote.turnaround_label + ' production' : 'Same-day production';
      // Each production-time choice's charge at this price, as B2Sign shows it: "+$8.00" or "Free".
      if (quote.turnaround_options) {
        form.querySelectorAll('[data-turnaround-option]').forEach((el) => {
          const amount = quote.turnaround_options[el.dataset.value];
          const label = el.querySelector('[data-turnaround-price]');
          if (label && typeof amount === 'number') label.textContent = amount > 0 ? '+$' + money(amount) : 'Free';
        });
      }
      // The add-to-cart handler applies the discount to this value before submitting.
      form.querySelectorAll('#totalCost').forEach((input) => { input.value = quote.subtotal.toFixed(2); });
      addButtons.forEach((button) => { button.disabled = false; });
    };

    // Channel letters: the text measured in the chosen font as B2Sign measures it, with the same
    // code as the builder (js/cl-measure.js), and sent as cl_measure, so the server prices every
    // separate piece of every letter by its own size (a W is wider than tall, a lowercase letter
    // shorter, the dot of an i a small piece) as the builder and its cart do
    // (wholesale_cl_request_measure() in inc/pricing.php). Canvas metrics at 200px: the server
    // scales them to every height it prices, the other heights' prices included.
    const letterInput = form.querySelector('#letterInput');
    let measureInput = null;
    if (letterInput && window.SfsLetterMeasure) {
      measureInput = form.querySelector('input[name="cl_measure"]');
      if (!measureInput) {
        measureInput = document.createElement('input');
        measureInput.type = 'hidden';
        measureInput.name = 'cl_measure';
        form.appendChild(measureInput);
      }
    }
    // This page's font names as the builder codes them (fontData in js/cl.js).
    const builderFont = (value) => ({ 'arial black': 'arial-black' })[String(value || '').trim().toLowerCase()] || String(value || 'Arial').replace(/"/g, '');
    let measureFont = '';
    const measureSignal = scope.signal;
    // Fills cl_measure and the width shown under the height for the form as it is now; true when it changed.
    const syncLetterMeasure = () => {
      if (!measureInput) return false;
      const before = measureInput.value;
      const family = builderFont(form.querySelector('select[name="font"]')?.value);
      const measured = window.SfsLetterMeasure.measure(letterInput.value, family);
      measureInput.value = window.SfsLetterMeasure.payload(measured);
      // The sign's width as B2Sign measures it, at the chosen height (the raceway is priced on it).
      const height = parseFloat(form.querySelector('select[name="height"] option:checked')?.dataset.size) || 0;
      const sized = window.SfsLetterMeasure.at(measured, height);
      const widthDisplay = document.getElementById('widthDisplay');
      if (sized && widthDisplay) {
        widthDisplay.innerHTML = `Approximate Width: <b> ${sized.width.toFixed(1)} Inches</b>`;
      }
      // Measured in a fallback font until the chosen one has downloaded: measured again then.
      if (document.fonts && family !== measureFont) {
        measureFont = family;
        document.fonts.load(`200px "${family}"`).then(() => {
          if (measureSignal.aborted) return;
          window.SfsLetterMeasure.clear();
          if (syncLetterMeasure()) refresh(0);
        }).catch(() => {});
      }
      return measureInput.value !== before;
    };

    let timer;
    let requestId = 0;
    // Asks the server. In the foreground (no cached price) the box shows it is updating and
    // Add To Cart waits for the answer; in the background (a cached price is already shown)
    // it only brings the prices of the choices after this one.
    const fetchQuote = async (foreground) => {
      const thisRequest = ++requestId;
      const entries = formEntries();
      const key = cacheKey(entries);
      const data = new FormData(form);
      data.delete('custom-artwork');
      data.append('action', 'wholesale_price_quote');
      data.append('variants', JSON.stringify(variantList(entries)));
      if (foreground) pricingBox.classList.add('is-updating');
      try {
        const response = await fetch(window.wholesaleShop.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' });
        const quote = await response.json();
        // Cached even when the visitor has moved on: these prices are for the form as sent.
        (quote.variants || []).forEach(([name, value, variantQuote]) => {
          cachePut(cacheKey(entries.map(([n, v]) => [n, n === name ? value : v])), variantQuote, false);
        });
        delete quote.variants;
        cachePut(key, quote, true);
        if (thisRequest !== requestId) return;
        applyQuote(quote);
      } catch (error) {
        // Keep the browser-side estimate; the cart still prices the item on the server.
      } finally {
        if (thisRequest === requestId) {
          pricingBox.classList.remove('is-updating');
          settleQuote();
        }
      }
    };

    const refresh = (delay = 250) => {
      clearTimeout(timer);
      form.dataset.quoting = '1';
      // One tick later, so other handlers of the same change (a custom dropdown filling its
      // hidden select, a choice that resets another) have run before the form is read.
      timer = setTimeout(() => {
        syncLetterMeasure();
        if (sizeIsEmpty()) {
          ++requestId;
          // No size yet (or a box cleared while typing a new one): show the minimum charge,
          // as B2Sign does, without asking the server for a quote that can only fail.
          const minEveryday = parseFloat(sizeSummary.dataset.minEveryday) || 0;
          const minTotal = parseFloat(sizeSummary.dataset.minTotal) || 0;
          showPrices(minEveryday, minEveryday - minTotal, minTotal);
          if (minChargeText) minChargeText.hidden = false;
          message.hidden = true;
          turnaroundRow.hidden = true;
          pricingBox.classList.remove('is-updating');
          // Add To Cart stays clickable; the size guard below sends the visitor to the size box.
          addButtons.forEach((button) => { button.disabled = false; });
          settleQuote();
          return;
        }
        // Channel letters with no sign text yet: the quote can only fail ("Enter the text
        // for your sign..."), which the report counted as a pricing error although the
        // visitor sees this prompt. Show it without asking the server.
        const letterInput = form.querySelector('#letterInput');
        if (letterInput && !letterInput.value.trim()) {
          ++requestId;
          message.textContent = 'Type your sign text above to see your exact price.';
          message.classList.add('is-hint');
          message.hidden = false;
          pricingBox.classList.remove('is-updating');
          addButtons.forEach((button) => { button.disabled = false; });
          settleQuote();
          return;
        }
        const hit = cacheGet(cacheKey(formEntries()));
        if (hit) {
          // Priced already: show it now (the exact server answer) and let Add To Cart go.
          ++requestId;
          applyQuote(hit.quote);
          pricingBox.classList.remove('is-updating');
          settleQuote();
          // Fetch the next round of choices unless this answer already brought them.
          if (!hit.primed) timer = setTimeout(() => fetchQuote(false), delay);
          return;
        }
        timer = setTimeout(() => fetchQuote(true), delay);
      }, 0);
    };

    form.addEventListener('change', () => refresh(60));
    form.addEventListener('input', (event) => {
      // A number box waits for a short typing pause, so "24" isn't first priced (and
      // rejected as too small) at "2" on a phone keyboard; leaving the box prices it at once.
      // A size or quantity priced before shows at once from the cache.
      if (event.target.matches('input[type="number"]')) refresh(450);
      else if (event.target.matches('#letterInput')) refresh(250);
    });
    // Custom color pickers update hidden selects without bubbling a native change event.
    document.addEventListener('click', (event) => {
      if (event.target.closest('.custom-select li')) refresh(60);
    }, { signal: scope.signal });
    refresh(0);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
  document.addEventListener('wholesale:product-swapped', init);
})();

// "Shop by sign type" rail: show prev/next arrows only while there is more to scroll.
(() => {
  const init = () => {
    document.querySelectorAll('[data-types-rail]').forEach((rail) => {
      const list = rail.querySelector('[data-types-list]');
      const prev = rail.querySelector('[data-types-prev]');
      const next = rail.querySelector('[data-types-next]');
      if (!list || !prev || !next) return;

      const update = () => {
        const max = list.scrollWidth - list.clientWidth;
        prev.hidden = list.scrollLeft <= 4;
        next.hidden = max <= 4 || list.scrollLeft >= max - 4;
      };
      const scrollBy = (direction) => {
        list.scrollBy({ left: direction * list.clientWidth * 0.8, behavior: 'smooth' });
      };

      prev.addEventListener('click', () => scrollBy(-1));
      next.addEventListener('click', () => scrollBy(1));
      list.addEventListener('scroll', update, { passive: true });
      window.addEventListener('resize', update);
      update();
    });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

// Image skeletons (css/ui-polish.css section 7): a product thumbnail frame
// shimmers until the photo it is showing has loaded (or failed). When a frame
// switches photo (card gallery arrow/swipe, product page thumbnail click) it
// gets .sk-swap, so the placeholder covers the old photo until the new one is in.
(function () {
  const frames = '.pb-image-top, .sc-card-media, .sf-type-image, .related-product-image, .home-types-image, .product-image-zoom, .gallery-image-item, .srp-card__media, .cla-style-image, .bd-product-image, .sc-cat-tile-img, .cart-line-image';
  // The photo on show: the active slide in a card gallery, otherwise the only image.
  const shownImage = (frame) => frame.querySelector('img.pb-gallery-image.is-active') || frame.querySelector('img');
  const isReady = (img) => !img || (img.complete && Boolean(img.getAttribute('src') || img.getAttribute('srcset')));
  const sync = (frame, isChange) => {
    const img = shownImage(frame);
    frame.skImage = img;
    frame.skSrc = img ? img.getAttribute('src') : null;
    if (isReady(img)) {
      frame.classList.add('sk-done');
      frame.classList.remove('sk-swap');
    } else {
      frame.classList.remove('sk-done');
      if (isChange) frame.classList.add('sk-swap');
    }
  };
  const frameOf = (node) => (node && node.tagName === 'IMG' ? node.closest(frames) : null);

  document.querySelectorAll(frames).forEach((frame) => sync(frame, false));
  // A product group swap (js/product-group-swap.js) brings in new frames.
  document.addEventListener('wholesale:product-swapped', () => {
    document.querySelectorAll(frames).forEach((frame) => sync(frame, false));
  });

  // Load and error don't bubble; capture catches lazy images loading later.
  const onImage = (event) => {
    const frame = frameOf(event.target);
    if (frame) sync(frame, false);
  };
  document.addEventListener('load', onImage, true);
  document.addEventListener('error', onImage, true);

  // A new src, or another gallery slide becoming active, is a photo change.
  // The observer runs before the next paint, so the old photo never flashes.
  new MutationObserver((records) => {
    const changed = new Set();
    records.forEach((record) => {
      const frame = frameOf(record.target);
      if (frame) changed.add(frame);
    });
    // Only a different photo counts; class churn on the same slide (e.g. the
    // gallery setup on page load) keeps the first-load behaviour.
    changed.forEach((frame) => {
      const img = shownImage(frame);
      sync(frame, img !== frame.skImage || (img && img.getAttribute('src') !== frame.skSrc));
    });
  }).observe(document.body, { subtree: true, attributes: true, attributeFilter: ['src', 'srcset', 'class'] });
})();

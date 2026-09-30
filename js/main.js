(function ($) {
  $(document).ready((e) => {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const revealRoots = ['main', '#content', '.home', '.home-hero', '.landing-page', '.product-details', '.contact-page', '.site-main'];
    const revealTargets = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'li', 'figure', 'img', 'svg', 'blockquote', '.card', '.btn', 'form', 'table', 'section', '.home-hero-copy', '.home-hero-actions', '.home-hero-feature'];
    const revealSelector = revealRoots
      .flatMap((root) => revealTargets.map((target) => `${root} ${target}`))
      .concat('.footer > *')
      .join(',');
    const revealElements = [...document.querySelectorAll(revealSelector)].filter((element, index, elements) => {
      // The hero stays static so ad visitors see the headline and CTAs immediately.
      return !element.matches('.pb-gallery-image, .cl-icon, .home-hero')
        && !element.closest('header, nav, .modal, .offcanvas, .home-hero-content, .cla-hero, .cla-quote-wrap, .checkout-page, .cart-page-v2, .account-page-v2, .order-confirmation, .footer-cp-text-container, [aria-hidden="true"]')
        && elements.indexOf(element) === index;
    });

    document.documentElement.classList.add('scroll-effects-enabled');

    const galleries = [...document.querySelectorAll('[data-gallery]')];
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
    });

    galleries.forEach((gallery) => {
      setGalleryImage(gallery, 0);
    });

    if (prefersReducedMotion || !('IntersectionObserver' in window)) {
      revealElements.forEach((element) => element.classList.add('scroll-reveal', 'is-visible'));
    } else {
      revealElements.forEach((element, index) => {
        element.classList.add('scroll-reveal');
        element.style.setProperty('--scroll-delay', `${Math.min(index % 5, 4) * 60}ms`);
      });

      const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.12, rootMargin: '0px 0px -40px' });

      revealElements.forEach((element) => revealObserver.observe(element));
    }

    let discountPercent = parseInt($('#discountPercent').val());
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

      $('#widthDisplay').html(`Aproximate Width: <b> ${totalWidth.toFixed(1)} Inches</b>`)
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
      let subTotalPrice = (subPrice).toFixed(2)
      let totalPrice = (discountPercent ? price - ((price / 100) * discountPercent) : price).toFixed(2)


      // display saving price
      $(".product-pricing-box .price-subtotal-container  .price-saving").text(
        formatNumberWithCommas(((subPrice / 100) * discountPercent).toFixed(2))

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
      let totalHeightIn = (height * 12).toFixed(1);
      let totalWidthIn = (width * 12).toFixed(1);
      let totalSqft = height * width;
      let pricePerSqft = parseFloat(
        $(".product-attibute-box #pricePerSqft").val()
      );
      let subTotalPrice = pricePerSqft * totalSqft;
      let totalPrice = pricePerSqft * totalSqft;
      let dimentionText = `${totalHeightIn}" x ${totalWidthIn}" = ${totalSqft.toFixed(
        2
      )} ft<sup>2</sup> `;
      // display dimention
      $(".product-attibute-box .total-size-sqft").html(dimentionText);
      $(".product-attibute-box .total-size-sqft").attr(
        "data-total-sqft",
        totalSqft
      );
      updatePrice(subTotalPrice, totalPrice,'updateDisplay');
      // change slelects to default


      $(".product-attibute-box .dynamic-select:not(.avoid-price)").each(function (e) {
        let firstOptionVal = $(this).children().first().val()
        $(this).val(firstOptionVal);
        $(this).attr("data-cCost", 0)
      });

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
      "change",
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

        if (costType == "%") {
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
          if (minSqft > totalSqft) {
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
    // change dimension inputs
    $(document).on("change", ".product-attibute-box .dimenstion-calculator input", (e) => {
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
      resetTurnaround();


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

    $(".gallery-image-item").click((e) => {
      let containerHeight = $(".thumbnail-container").innerHeight();
      let imageSrc = $(e.currentTarget).attr("data-image");
      $(".thumbnail-container .single-product-thumbnail").removeAttr('srcset')
      $(".thumbnail-container .single-product-thumbnail").attr("src", imageSrc);
      $(".thumbnail-container").css("height", containerHeight);
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

    $('.option-info-button').click(e => {
      $(e.currentTarget).siblings('.option-info-content').toggle();
    })


    $('.cart-details-toggler').click(e => {
      let currentTarget = e.currentTarget
      $(currentTarget).parent().siblings('.cart-details-container').toggle();
    })

    $('#allProductsBtn').click( e => {
      e.stopPropagation();
      const button = $(e.currentTarget);
      const menu = $('#megaMenu');
      const isOpen = menu.is(':visible');

      menu.stop(true, true).toggle(!isOpen);
      button.attr('aria-expanded', String(!isOpen));
      $('.main-header').toggleClass('mega-menu-open', !isOpen);
      if (!isOpen) sizeMegaMenu();
    })

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
    }

    $('.mega-menu-backdrop').click(closeMegaMenu);


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
      $('#totalCost').val(costAfterDiscount)
      if (costBeforeDiscount <= 0) {
        e.preventDefault();
        $(e.currentTarget).prop('disabled', true);
        return;
      }
      if (totalSqft < minSqft) {
        e.preventDefault();
        alert('Minimum: ' + minSqft + 'sqft')
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

    $(document).on('change', 'input[name="turnaround_option"]', e => {
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

    const windowUrl = new URL(window.location.href);
    // Use URLSearchParams to get query string parameters
    const params = new URLSearchParams(windowUrl.search);
    for (const [key, value] of params) {
      let querySelector = (`#select-${(key)}`)
      let currentAttr = $(querySelector);
      if (currentAttr) {
        currentAttr.val(value);
        currentAttr.trigger('change');
      }

    }
  });
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
  const init = () => {
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

    let turnaroundRow = pricingBox.querySelector('.price-turnaround-row');
    if (!turnaroundRow) {
      turnaroundRow = document.createElement('div');
      turnaroundRow.className = 'row price-turnaround-row';
      turnaroundRow.hidden = true;
      turnaroundRow.innerHTML = '<div class="col-6"><span class="fs-6">Same-day production</span></div><div class="col-6 price-subtotal-container"><span class="fs-6 d-block">+$<span class="price-turnaround"></span></span></div>';
      const anchor = pricingBox.querySelector('.totalSaving-container') || pricingBox.querySelector('.subtotal-container');
      anchor?.after(turnaroundRow);
    }

    let timer;
    let requestId = 0;
    const refresh = () => {
      clearTimeout(timer);
      timer = setTimeout(async () => {
        const data = new FormData(form);
        data.delete('custom-artwork');
        data.append('action', 'wholesale_price_quote');
        const thisRequest = ++requestId;
        pricingBox.classList.add('is-updating');
        try {
          const response = await fetch(window.wholesaleShop.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' });
          const quote = await response.json();
          if (thisRequest !== requestId) return;

          if (!quote.ok) {
            message.textContent = quote.error;
            message.hidden = false;
            addButtons.forEach((button) => { button.disabled = true; });
            return;
          }

          message.hidden = true;
          pricingBox.querySelectorAll('.price-subtotal').forEach((el) => { el.textContent = money(quote.subtotal); });
          pricingBox.querySelectorAll('.price-saving').forEach((el) => { el.textContent = money(quote.discount); });
          pricingBox.querySelectorAll('.price-total').forEach((el) => { el.textContent = money(quote.total); });
          turnaroundRow.hidden = !(quote.turnaround > 0);
          turnaroundRow.querySelector('.price-turnaround').textContent = money(quote.turnaround);
          // The add-to-cart handler applies the discount to this value before submitting.
          form.querySelectorAll('#totalCost').forEach((input) => { input.value = quote.subtotal.toFixed(2); });
          addButtons.forEach((button) => { button.disabled = false; });
        } catch (error) {
          // Keep the browser-side estimate; the cart still prices the item on the server.
        } finally {
          if (thisRequest === requestId) pricingBox.classList.remove('is-updating');
        }
      }, 250);
    };

    form.addEventListener('change', refresh);
    form.addEventListener('input', (event) => {
      if (event.target.matches('#letterInput, input[type="number"]')) refresh();
    });
    // Custom color pickers update hidden selects without bubbling a native change event.
    document.addEventListener('click', (event) => {
      if (event.target.closest('.custom-select li')) refresh();
    });
    refresh();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

(function ($) {
  $(document).ready((e) => {

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
            .replace(" ", "").length;
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
      let letterCharacters = e.target.value.replace(" ", "").length;
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
      let letterCharacters = $("#letterInput").val().replace(" ", "").length;
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

    // handle mobile menu close button click

    $(".mobile-menu-close, .mobile-menu-container .menu-item").click((e) => {
      $(".mobile-menu-container").slideUp();
    });

    // handel hamberger menu click

    $(".mobile-menu-trigger").click((e) => {
      $(".mobile-menu-container").slideDown("fast");
    });

    $('.category-filter-toggler i').click(e => {
      $('.menu-filter-menu-container').toggle()
      $(e.currentTarget).toggleClass('fa-times')
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
      $('#megaMenu').toggle();
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
    let updateGrandTotal = (subTotal,shiipingCost) => {
      let grandTotal = parseFloat(subTotal + shiipingCost).toFixed(2)
      $('#grandTotal').val(grandTotal);

    }
    // shipping cost radio change
    $('.shipping-radio[name="shipping_method"]').change(function () {
      let currentShippingCost = parseFloat($('#shippingCost').val())
      let newShippingCost = parseFloat($(this).val());

      let checkoutSubTotal = parseFloat($('#subTotal').val());
      let productTurnaround = parseInt($('#productTurnaround').val());
      let productCategory = $('#productCategory').val();

      if (productCategory != 'adhesive-products') {
        switch (newShippingCost) {
          case 50:
            $('#estimateDeliveryText').text(`Order in the next 12 hrs and your order will ship by ${formatDateWithAddedDays(productTurnaround + 5)}`);
            $('#estimateDeliveryTime').val(formatDateWithAddedDays(productTurnaround + 5))
            updateGrandTotal(checkoutSubTotal,50)
            break;
          case 200:
            $('#estimateDeliveryText').text(`Order in the next 12 hrs and your order will ship by ${formatDateWithAddedDays(productTurnaround + 3)}`);
            $('#estimateDeliveryTime').val(formatDateWithAddedDays(productTurnaround + 3))
            updateGrandTotal(checkoutSubTotal,200)

            break;
          case 250:
            $('#estimateDeliveryText').text(`Order in the next 12 hrs and your order will ship by ${formatDateWithAddedDays(productTurnaround + 2)}`);
            $('#estimateDeliveryTime').val(formatDateWithAddedDays(productTurnaround + 2))
            updateGrandTotal(checkoutSubTotal,250)

            break;
          case 300:
            $('#estimateDeliveryText').text(`Order in the next 12 hrs and your order will ship by ${formatDateWithAddedDays(productTurnaround + 0)}`);
            $('#estimateDeliveryTime').val(formatDateWithAddedDays(productTurnaround))
            updateGrandTotal(checkoutSubTotal,300)

            break;

        }
      } else {
        switch (newShippingCost) {
          case 12.5:
            $('#estimateDeliveryText').text(`Order in the next 12 hrs and your order will ship by ${formatDateWithAddedDays(productTurnaround + 5)}`);
            $('#estimateDeliveryTime').val(formatDateWithAddedDays(productTurnaround + 5))
            updateGrandTotal(checkoutSubTotal,12.5)

            break;
          case 50:
            $('#estimateDeliveryText').text(`Order in the next 12 hrs and your order will ship by ${formatDateWithAddedDays(productTurnaround + 3)}`);
            $('#estimateDeliveryTime').val(formatDateWithAddedDays(productTurnaround + 3))
            updateGrandTotal(checkoutSubTotal,50)

            break;
          case 62.5:
            $('#estimateDeliveryText').text(`Order in the next 12 hrs and your order will ship by ${formatDateWithAddedDays(productTurnaround + 2)}`);
            $('#estimateDeliveryTime').val(formatDateWithAddedDays(productTurnaround + 2))
            updateGrandTotal(checkoutSubTotal,62.5)

            break;
          case 75:
            $('#estimateDeliveryText').text(`Order in the next 12 hrs and your order will ship by ${formatDateWithAddedDays(productTurnaround)}`);
            $('#estimateDeliveryTime').val(formatDateWithAddedDays(productTurnaround));
            updateGrandTotal(checkoutSubTotal,75)

            break;

        }
      }
      let totalTax = parseFloat($('#totalTax').val()).toFixed(2)
      console.log(checkoutSubTotal,newShippingCost,parseFloat(totalTax))
      let grandTotal = parseFloat((checkoutSubTotal) + newShippingCost + parseFloat(totalTax));
      $('.grand-total-holder').text('$' + numberWithCommas(grandTotal.toFixed(2)))
      $('.shipping-cost-holder').text('$' + newShippingCost)
      $('#shippingCost').val(newShippingCost)
    })


    $('#addToCartBtn').click(e => {
      let minSqft = parseFloat($('#minSqft').val());
      let totalSqft = parseFloat($('#totalSqft').val());
      let costBeforeDiscount = parseFloat($('#totalCost').val()).toFixed(2);
      let discountCost = ((costBeforeDiscount * discountPercent) / 100).toFixed(2)
      let costAfterDiscount = costBeforeDiscount - discountCost
      $('#totalCost').val(costAfterDiscount)
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

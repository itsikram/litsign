<?php
//Template Name: b2 caculator
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>B2 Calculator</title>

</head>

<body>
    <!-- The HTML template you want to save -->

    <style>
        * {
            margin: 0;
        }

        .inputsContainer {
            margin-bottom: 20px;
            position: fixed;
            width: 100%;
            top: 0;
            background: gray;
            padding: 10px;
        }

        .inputsContainer label {
            color: #fff;
            font-size: 18px
        }

        input {
            padding: 5px;
            font-size: 20px;
            border-radius: 10px;
            outline: 0;
            border: 0;
        }

        #template {
            margin-top: 10px;
        }

        #variablePriceDisplayer {

            overflow-y: scroll;
            list-style: none;
        }

        #variablePriceDisplayer li {
            padding: 4px 0;
        }
    </style>
    <div id="template">
        <div style="overflow: hidden;" class="inputsContainer">
            <div style="width: 40%; float: left;">
                <label for="priceInput"> Main Price: </label>
                <input value="0" type="number" id="priceInput" />
            </div>
            <div style="width: 60%; float: left;">
                <label for="variablePriceInput"> Variable Price: </label>
                <input value="0" type="number" id="variablePriceInput" />
                <button style="padding:5px; font-size: 16px; border-radius: 10px; cursor: pointer"
                    id="updatePrice">Update Price</button>
            </div>

        </div>
        <div style="overflow: hidden; padding-top: 50px; text-align:center">
            <!-- <button id="updateButton"> Update Price </button> -->
            <div style="width: 50%; float: left;">
                <h1 id="priceDisplayerLabel" style="font-size: 20px;">Main Price:</h1>
                <h2 id="priceDisplayer"> $0</h2>

                <h1 id="startingPriceLabel" style="font-size: 20px; margin-top: 20px">Starting Price:</h1>
                <h2 id="StartingPriceDisplayer">$0</h2>

            </div>
            <div style="width: 50%; float: left; text-align:center">
                <ol id="variablePriceDisplayer">

                </ol>
                <button style="padding:5px; font-size: 18px;margin-top: 10px" id="variablePriceReseter">Reset Variable
                    Data</button>
            </div>
        </div>


    </div>


    <script>
        let priceInput = document.getElementById('priceInput')
        let variablePriceInput = document.getElementById('variablePriceInput')
        let priceDisplayer = document.getElementById('priceDisplayer')
        let updatePrice = document.getElementById('updatePrice')

        let StartingPriceDisplayer = document.getElementById('StartingPriceDisplayer')
        let variablePriceDisplayer = document.getElementById('variablePriceDisplayer')

        let startingPriceLabel = document.getElementById('startingPriceLabel')
        let priceDisplayerLabel = document.getElementById('priceDisplayerLabel')


        let updateButton = document.getElementById('updateButton')

        let variableIndex = 0;
        let startingPrice = 0
        let updatedStartingPrice = 0

        priceDisplayer.style.cursor = 'pointer';

        const clickEvent = new CustomEvent("click", {
            bubbles: true
        })
        const changeEvent = new CustomEvent("change", {
            bubbles: true
        })


        priceInput.value = 0
        variablePriceInput.value = 0

        updatePrice.addEventListener('click', e => {
            let variablePrice = parseFloat(variablePriceInput.value || 0);
            priceInput.value = variablePrice
            priceInput.dispatchEvent(changeEvent);

        })

        priceDisplayerLabel.addEventListener('click', e => {
            priceDisplayer.dispatchEvent(clickEvent);
        })

        variablePriceReseter.addEventListener('click', e => {
            [...document.querySelectorAll('#variablePriceDisplayer li')].forEach(e => {
                e.remove();
                variablePriceInput.value = 0

            })
        })

        variablePriceInput.addEventListener('focus', e => {
            variablePriceInput.select();
        })
        priceInput.addEventListener('focus', e => {
            priceInput.select();
        })

        priceInput.addEventListener('change', e => {
            let mainPrice = parseFloat(e.target.value || 0);
            startingPrice = mainPrice
            let extraPrice = (mainPrice / 100) * 50
            let updatedPrice = (mainPrice + extraPrice).toFixed(2)
            updatedStartingPrice = updatedPrice;



            priceDisplayer.innerHTML = `$${updatedPrice}`;
            priceDisplayer.dataset.cost = updatedPrice
            const pricePerItem = document.createElement('span')
            pricePerItem.style.cursor = 'pointer'
            pricePerItem.innerHTML = ` $${updatedPrice} per item `
            const priceSeparetor = document.createElement('span')
            priceSeparetor.innerHTML = ' || ';
            const pricePerSqft = document.createElement('span')
            pricePerSqft.style.cursor = 'pointer'

            pricePerSqft.innerHTML = ` $${updatedPrice} per ft<sup>2</sup> `;

            StartingPriceDisplayer.innerHTML = '';

            StartingPriceDisplayer.appendChild(pricePerItem)
            StartingPriceDisplayer.appendChild(priceSeparetor)
            StartingPriceDisplayer.appendChild(pricePerSqft)

            pricePerItem.addEventListener('click', e => {
                navigator.clipboard.writeText(`$${updatedPrice} per item `);

                pricePerItem.innerHTML = ` $${updatedPrice} per item - Copied `

                setTimeout(e => {
                    pricePerItem.innerHTML = ` $${updatedPrice} per item `

                }, 1000)
            })

            pricePerSqft.addEventListener('click', e => {
                navigator.clipboard.writeText(` $${updatedPrice} per ft<sup>2</sup> `);

                pricePerSqft.innerHTML = ` $${updatedPrice} per ft<sup>2</sup> - Copied `

                setTimeout(e => {
                    pricePerSqft.innerHTML = ` $${updatedPrice} per ft<sup>2</sup> `

                }, 1000)
            })

        })

        priceDisplayer.addEventListener('click', e => {
            let costDataset = priceDisplayer.dataset.cost
            if (costDataset) {
                navigator.clipboard.writeText(costDataset);
                priceDisplayer.innerHTML = `$${costDataset} - Copied`;
                setTimeout(() => {
                    priceDisplayer.innerHTML = `$${costDataset}`;
                }, 1000);
            }


        })
        variablePriceInput.addEventListener('change', e => {
            let variablePrice = parseFloat(e.target.value || 0);
            let updatedVariablePrice = (((variablePrice - startingPrice)) * 1.5).toFixed(2)


            variableIndex += 1

            const variableItemContainer = document.createElement('li');
            variableItemContainer.style.fontSize = `24px`;
            variableItemContainer.style.cursor = `pointer`;
            variableItemContainer.dataset.cost = updatedVariablePrice
            variableItemContainer.innerHTML = `#${variableIndex} ($${variablePrice}) =  $${updatedVariablePrice}`;
            variablePriceDisplayer.appendChild(variableItemContainer);

            variableItemContainer.addEventListener('click', (e) => {
                let variableCost = e.target.dataset.cost;
                navigator.clipboard.writeText(variableCost);
                variableItemContainer.innerHTML = `($${variablePrice}) =  $${updatedVariablePrice} - Copied`;

                setTimeout(() => {
                    variableItemContainer.innerHTML = `#${variableIndex} ($${variablePrice}) =  $${updatedVariablePrice}`;

                }, 1000)
            })

        })

    </script>
</body>

</html>
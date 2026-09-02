
const { createStore, combineReducers } = window.Redux;
let detailTableBody = document.getElementById('detailTableBody');
// start constant
const container = document.getElementById('container');
const previewContainer = document.getElementById('previewContainer');
const textInput = document.getElementById('textInput');
const fontSelect = document.getElementById('fontSelect');
const faceColorPicker = document.getElementById('faceColorPicker');
const trimCapColorPicker = document.getElementById('trimcapColorPicker');
const trimcapSizeInput = document.getElementById('trimcapSizeInput');

const returnColorPicker = document.getElementById('returnColorPicker');
const returnSizeInput = document.getElementById('returnSizeInput');
const sizeHeightInput = document.getElementById('sizeHeightInput');
const sizeWidthInput = document.getElementById('sizeWidthInput');
const cornerRadiusInput = document.getElementById('cornerRadius');
const fontFamilyLoader = document.getElementById('fontFamilyLoader');
const addTextBtn = document.getElementById('addTextBtn');
const duplicateBtn = document.getElementById('duplicateBtn');
const saveBtn = document.getElementById('saveBtn');
const elementIndexContainer = document.querySelector('.element-index');
const elementDimenstionContainer = document.querySelector('.element-dimenstion');
const standarPsCost = parseFloat(document.getElementById('standarPsCost').value);
const backLitCost = parseFloat(document.getElementById('backLitCost').value);
const eightFtcableCost = parseFloat(document.getElementById('eightFtcableCost').value);
const hasTrimcap = document.getElementById('hasTrimcap').value;
const hasReturn = document.getElementById('hasReturn').value;
const hasFace = document.getElementById('hasFace').value;
let hasDualColor = false;
let dualColorBg = null;


const productPermalink = document.getElementById('productPermalink').value;

const undoBtn = document.getElementById('undoBtn');
const redoBtn = document.getElementById('redoBtn');
const deleteBtn = document.getElementById('deleteBtn');
const addRacewayButton = document.getElementById('addRacewayButton');
let sliderCloseButton = document.getElementById('sliderCloseBtn')
const infoButtons = document.querySelectorAll('.info-btn');
let shapeButtons = document.querySelectorAll('.shape-dropdown .shapes-container .shape');
let bottomBarListItems = document.querySelectorAll('.bottombar-list-item')
let sidebarListItems = document.querySelectorAll('.slider-choose-item-container.slider-choose-item-container');
let productClData = JSON.parse(document.getElementById('productClData').value);
let editDesignDataInput = document.getElementById('editDesignData').value;
var isEditDesign = true;
let editDesignData = editDesignDataInput.length > 1 ? JSON.parse(editDesignDataInput) : isEditDesign = false;
let editDesignElements = editDesignData.elements || [];
let editDesignExtras = editDesignData.extras || [];
let isLitOption = detailTableBody.dataset.haslitoption == "0" ? false : true;

let siteUrl = null;
if (document.location.origin == 'http://localhost') {
    siteUrl = document.location.origin + '/storefrontsignonline.com';
} else {
    siteUrl = document.location.origin;

}


let fontData = [
    {
        heading: 'Fonts',
        cost: 0,
        id: 'font',
        options: [{
            'Arial': 'Arial',
        },
        {
            'Arial Black': 'arial-black',
        },
        {
            'Gotham Medium': 'gotham-medium',
        },
        {
            "Halvetica": 'helvetica'
        },
        {
            'Helvetica Condensed Bold': 'helvetica-condensed-bold',
        },
        {
            'Helvetica Rounded Bold': 'helvetica-rounded-bold',
        },


        ]
    }
]

let defaultColorDataInput = document.getElementById('defaultColorData').value;
let defaultColorData = {}

const dpi = 10; // Assuming 96 DPI for inch to pixel conversion
const ppi = 10;
const triangleReduction = 1.15 // 1.32;

let colorCost = 0;


let currentElementIndex = 1;

let widhtDisplay = null
let heightDisplay = null

var heightArrows = null;
var widthArrows = null;

//initial input values
var fontSize = 10 * dpi;
var fontFamily = 'Arial';
var trimCapColor = 'gray';
var trimCapColorTitle = null;
var faceColor = null;
var faceColorTitle = null;
var returnColor = '#000000';
var returnColorTitle = null;
var returnSize = 3;
var returnSizeTitle = 5;
var trimCapSize = 2;
var trimCapSizeTitle = null;
var isReturnColorSame = false;
var maxWidth = 360;
var maxHeight = 360;
var minWidth = 80;
var minHeight = 80;

let contentWidth = 0;
let contentHeight = 0;

// var changeEvent = new CustomEvent("change",{
//     bubbles: true
// })

let selectedNode = null;
let selectedNodeType = null;


let canvasWidth = container.clientWidth;
let canvasHeight = container.clientHeight;


let previewCanvasWidth = previewContainer.clientWidth;
let previewCanvasHeight = previewContainer.clientHeight;

let nodeLists = [];
let previewNodeLists = [];
let currentPreviewNode = null;

const undoStack = [];
const redoStack = [];

// end constant

// start utils

document.getElementById('leftSidebar').style.height = document.querySelector('.editor-container').clientHeight;

async function waitForWindowLoad() {
    await new Promise((resolve) => {
        window.addEventListener('load', resolve);
    });

    console.log('Window fully loaded, including all resources');
}

// Call the function
waitForWindowLoad();


let getDataById = (ids, productClDataJson) => {
    let productClDataList = [];

    ids.forEach((id) => {
        for (let i = 0; i < productClDataJson.length; i++) {
            if (productClDataJson[i].id == id) {
                productClDataList.push(productClDataJson[i])
            }
        }
    })

    return productClDataList;

}

let activeFontTitle = Object.keys(fontData[0].options[0])[0];
let activeFontCode = fontData[0].options[0][activeFontTitle];

// set default face Color
let faceColorData = getDataById(['color-ac'], productClData) ? getDataById(['color-ac'], productClData)[0].options[0] : undefined;
let activeFaceTitle = Object.keys(faceColorData)[0]
let activeFaceCode = faceColorData[Object.keys(faceColorData)[0]];
faceColor = activeFaceCode;
defaultColorData.face = `${activeFaceTitle}/${activeFaceCode}`

// set default return Color
let returnColorData = getDataById(['return-color'], productClData)[0].options[0];
let firstReturnColorValue = (returnColorData[Object.keys(returnColorData)[0]]).split('.')


let activeReturnColorTitle = Object.keys(returnColorData)[0]
let activeReturnColorCode = returnColorData[Object.keys(returnColorData)[0]];
returnColor = activeReturnColorCode;

defaultColorData.return = `${activeReturnColorTitle}/${activeReturnColorCode}`;


if (firstReturnColorValue[0] == 'same') {
    let returnSameAs = firstReturnColorValue[1]
    if (returnSameAs == 'face') {
        activeReturnColorTitle = Object.keys(faceColorData)[0]
        activeReturnColorCode = returnColorData[Object.keys(returnColorData)[0]];
        returnColor = activeFaceCode;
        isReturnColorSame = true;
    }
}

// set default trimcap Color

let trimcapColorData = hasTrimcap == 'on' ? getDataById(['trimcap-color'], productClData)[0].options[0] : null;
let activeTrimcapColorTitle = hasTrimcap == 'on' ? Object.keys(trimcapColorData)[0] : null;
let activeTrimcapColorCode = hasTrimcap == 'on' ? trimcapColorData[Object.keys(trimcapColorData)[0]] : null;
if (hasTrimcap == 'on') {
    trimCapColor = activeTrimcapColorCode
    defaultColorData.trimcap = `${activeTrimcapColorTitle}/${activeTrimcapColorCode}`

} else {
    trimCapColor =
        defaultColorData.trimcap = `${activeTrimcapColorTitle}/${activeTrimcapColorCode}`
}

// set default return Color
let returnSizeData = getDataById(['return-size'], productClData)[0].options[0];
let activeReturnSizeTitle = Object.keys(returnSizeData)[0]
let activeReturnSizeCode = parseInt(returnSizeData[Object.keys(returnSizeData)[0]].split('-')[0]);
returnSize = activeReturnColorCode;

// set default return Color
let trimcapSizeData = hasTrimcap == 'on' ? getDataById(['trimcap-size'], productClData)[0].options[0] : null;
let activeTrimcapSizeTitle = hasTrimcap == 'on' ? Object.keys(trimcapSizeData)[0] : null;
let activeTrimcapSizeCode = hasTrimcap == 'on' ? parseInt(trimcapSizeData[Object.keys(trimcapSizeData)[0]]) : null;
trimCapSize = activeTrimcapColorCode


if (defaultColorDataInput) {
    try {

        let defaultColorJson = JSON.parse(defaultColorDataInput)
        defaultColorData = defaultColorJson

        if (defaultColorJson.face) {
            activeFaceTitle = defaultColorData.face.split('/')[0]
            activeFaceCode = defaultColorData.face.split('/')[1]
            faceColor = defaultColorData.face.split('/')[1]
        }
        if (defaultColorJson.trimcap) {
            activeTrimcapColorTitle = defaultColorData.trimcap.split('/')[0]
            activeTrimcapColorCode = defaultColorData.trimcap.split('/')[1]
            trimCapColor = defaultColorData.trimcap.split('/')[1]
        }
        if (defaultColorJson.return) {

            if (defaultColorJson.return.split('.')[0] != 'same') {
                activeReturnColorTitle = defaultColorData.return.split('/')[0]
                activeReturnColorCode = defaultColorData.return.split('/')[1]
                returnColor = defaultColorData.return.split('/')[1]
            } else {
                defaultColorData.return = `${activeFaceTitle}/${activeFaceCode}`
                activeReturnColorTitle = activeFaceTitle
                activeReturnColorCode = activeFaceCode
                returnColor = activeFaceTitle
            }

        }
        if (defaultColorJson.color_cost) {
            colorCost = parseFloat(defaultColorJson.color_cost || 0)

        }

    } catch (error) {
        console.log('please setup default colors from dashboard')
        console.log(error)
    }

}

fontData.forEach(fontContainer => {
    let fontOptions = fontContainer.options
    let loaderChilds = ''
    fontOptions.forEach(option => {
        let fontName = Object.keys(option)[0];
        let fontValue = option[fontName]

        let loadFontItem = document.createElement('span')
        loadFontItem.innerText = fontName
        loadFontItem.style.fontFamily = fontValue

        loaderChilds += `<span style="font-family: ${fontValue}">${fontName}</span>`;

    })
    fontFamilyLoader.innerHTML = loaderChilds
    setTimeout(() => {
        fontFamilyLoader.remove()
    }, 2000);

})

var textFSI = [];

// active font
// let activeFontData = document.querySelector('.select-font').dataset.active
// let activeFontTitle = activeFontData.split('/')[0]
// let activeFontCode = activeFontData.split('/')[1]

// // active face
// let activeFaceData = document.querySelector('.select-face').dataset.active
// let activeFaceTitle = activeFaceData.split('/')[0]
// let activeFaceCode = activeFaceData.split('/')[1]


// // active face
// let activeReturnColorData = document.querySelector('.select-return').dataset.active
// let activeReturnColorTitle = activeReturnColorData.split('/')[0]
// let activeReturnColorCode = activeReturnColorData.split('/')[1]

// // active return size
// let activeReturnSizeData = document.querySelector('.select-return').dataset.activeSecond ? document.querySelector('.select-return').dataset.activeSecond : false;
// let activeReturnSizeTitle = activeReturnSizeData ? activeReturnSizeData.split('/')[0] : returnSizeTitle;
// let activeReturnSizeCode = activeReturnSizeData ? activeReturnSizeData.split('/')[1] : returnSize

// // active trimcap
// let activeTrimcapColorData = document.querySelector('.select-trimcap').dataset.active
// let activeTrimcapColorTitle = activeTrimcapColorData.split('/')[0]
// let activeTrimcapColorCode = activeTrimcapColorData.split('/')[1]

// // active face
// let activeTrimcapSizeData = document.querySelector('.select-trimcap').dataset.activeSecond || false;
// let activeTrimcapSizeTitle = activeTrimcapSizeData ? activeTrimcapSizeData.split('/')[0] : '1 Inch';
// let activeTrimcapSizeCode = activeTrimcapSizeData ? activeTrimcapSizeData.split('/')[1] : 3

// Define an initial state
let initialExtras = {
    'powerSupply': {
        'value': 'Standard',
        'cost': parseFloat(standarPsCost),
        'qty': 1
    },

    'cable': {
        'value': '3ft Cable',
        'cost': 0,
        'qty': 1
    }
};

if (isLitOption) {
    initialExtras.lit = {
        'value': 'Front Lit',
        'cost': 0,
        'qty': 1
    }
}
const initialElement = [];

// Define a reducer function
const extrasReducer = (state = initialExtras, action) => {
    switch (action.type) {
        case 'UPDATE':
            let payload = action.payload
            return { ...state, ...payload };
        case 'RESTORE_EXTRAS':

            return state = action.payload;
            break;
        default:
            return state;
    }
};
const elementsReducer = (state = initialElement, action) => {
    // activeFontData = document.querySelector('.select-font').dataset.active
    // activeFontTitle = activeFontData.split('/')[0]
    // activeFontCode = activeFontData.split('/')[1]

    // // active face
    // activeFaceData = document.querySelector('.select-face').dataset.active
    // activeFaceTitle = activeFaceData.split('/')[0]
    // activeFaceCode = activeFaceData.split('/')[1]


    // // active face
    // activeReturnColorData = document.querySelector('.select-return').dataset.active
    // activeReturnColorTitle = activeReturnColorData.split('/')[0]
    // activeReturnColorCode = activeReturnColorData.split('/')[1]

    // // active return size
    // activeReturnSizeData = document.querySelector('.select-return').dataset.activeSecond ? document.querySelector('.select-return').dataset.activeSecond : false;
    // activeReturnSizeTitle = activeReturnSizeData ? activeReturnSizeData.split('/')[0] : returnSizeTitle;
    // activeReturnSizeCode = activeReturnSizeData ? activeReturnSizeData.split('/')[1] : returnSize

    // // active trimcap
    // activeTrimcapColorData = document.querySelector('.select-trimcap').dataset.active
    // activeTrimcapColorTitle = activeTrimcapColorData.split('/')[0]
    // activeTrimcapColorCode = activeTrimcapColorData.split('/')[1]

    // // active face
    // activeTrimcapSizeData = document.querySelector('.select-trimcap').dataset.activeSecond || false;
    // activeTrimcapSizeTitle = activeTrimcapSizeData ? activeTrimcapSizeData.split('/')[0] : '1 Inch';
    // activeTrimcapSizeCode = activeTrimcapSizeData ? activeTrimcapSizeData.split('/')[1] : 3

    let nodeType = selectedNode ? selectedNode.getClassName() : null;
    switch (nodeType && selectedNode.getClassName()) {


        case 'Rect':
            nodeType = 'Rectangle'

            if (selectedNode.getAttr('textIndex')) {
                nodeType = 'Raceway';
            }
            break;


        case 'Line':
            nodeType = 'Arrow'
            break;

        case 'Star':
            nodeType = 'Starburst'

            break;

        case 'RegularPolygon':
            nodeType = 'Triangle'

            break;

    }

    let x = selectedNode ? selectedNode.x().toFixed(2) : null;
    let y = selectedNode ? selectedNode.y().toFixed(2) : null;

    let oldState = state;

    switch (action.type) {
        case 'ADD_ELEMENT':
            let font = selectedNode.getClassName() == 'Text' ? { title: activeFontTitle, code: activeFontCode } : undefined;
            let fontSize = selectedNode.getClassName() == 'Text' ? selectedNode.fontSize() : undefined;
            let radius = action.payload.radius / dpi || undefined;
            console.log('payload radius', action.payload.radius)
            let newElement = {
                ...action.payload,
                fontSize,
                font,
                type: nodeType,
                // faceColor: {
                //     title: activeFaceTitle,
                //     code: activeFaceCode
                // },
                // returnColor: {
                //     title: returnColorTitle,
                //     code: returnColorCode

                // },
                // trimcapColor: {
                //     title: trimCapColorTitle,
                //     code: trimcapColorCode

                // },
                // trimcapSize: {
                //     title: trimCapSizeTitle,
                //     code: trimcapSizeCode
                // },
                // returnSize: {
                //     title: returnSizeTitle,
                //     code: returnSizeCode

                // },
                // returnColor: {
                //     title: returnColorTitle,
                //     code: returnColorCode
                // },
                radius,
                x,
                y

            };
            // let textLenght = newElement.length;
            // let elementCost = (costPerInch * newElement.height) * textLenght;
            // newElement.cost = elementCost;
            return [...state, newElement];
            break;

        case 'REMOVE_ELEMENT':

            let newState = state.filter(el => el.id != action.payload.id)
            return newState;

            break;

        case 'UPDATE_ELEMENT':

            let elementId = action.payload.id;
            let oldState = state;

            for (let i = 0; i < state.length; i++) {
                if (state[i].id == elementId) {
                    let element = state[i];
                    let height = action.payload.height || element.height;
                    let width = action.payload.width || element.width;
                    let text = action.payload.text || element.text;
                    let cost = action.payload.cost || element.cost;
                    let radius = action.payload.radius / dpi || element.radius;
                    let colorCost = action.payload.colorCost == undefined ? element.colorCost : action.payload.colorCost;
                    let fontSize = selectedNode.getClassName() == "Text" ? selectedNode.fontSize() : undefined;

                    console.log('payload:',action.payload.cost,cost);

                    var updatedElement = {
                        ...element,
                        fontSize,
                        type: nodeType,
                        x,
                        y,
                        ...action.payload,
                        radius,
                        height,
                        width,
                        text,
                        cost,
                        colorCost,
                    }

                    state[i] = updatedElement;

                }
            }

            return state;

            break;
        case 'UPDATE_ID':

            let oldElementId = action.payload.id;
            let newId = action.payload.newId;
            for (let i = 0; i < state.length; i++) {
                if (state[i].id == oldElementId) {
                    let element = state[i];
                    state[i] = { ...state[i], id: newId }
                }
            }

            return state;

            break;
        case 'RESTORE_ELEMENT':
            state = action.payload;
            return state;
            break;
        default:
            return state;


    }

}

let getTextFSI = (id) => {
    for (let i = 0; i < textFSI.length; i++) {
        if (id == textFSI[i].id) {
            return textFSI[i].value;
        }
    }
}

let setTextFSI = (id, value) => {
    let idExists = false;
    for (let i = 0; i < textFSI.length; i++) {
        if (id == textFSI[i].id) {
            idExists = true;
            textFSI[i].value = value;
        }
    }

    if (!idExists) {
        textFSI.push({ id: id, value: value });
    }
}

let rootReducers = combineReducers({
    extras: extrasReducer,
    elements: elementsReducer
})
// Create a store with the reducer
const store = createStore(rootReducers);


let getNodeIndex = (node) => {

    let nodeId = 0;

    if (Number.isInteger(node)) {
        nodeId = node
    } else {
        nodeId = node.id
    }

    for (let i = 0; i < nodeLists.length; i++) {

        let listId = nodeLists[i].node._id;

        if (nodeId == listId) {
            return nodeLists[i].id;
        }
    }

}


let getNodeById = (nodeId) => {
    if (nodeId) {
        for (var i = 0; i < nodeLists.length; i++) {
            if (nodeLists[i].node._id == nodeId) {
                return nodeLists[i].node;
            }
        }
    }

}

let minimumSize = parseInt(Object.keys(getDataById(['cost-per-inch'], productClData)[0].options[0])[0]);
let maximumSize = getDataById(['cost-per-inch'], productClData)[0].options.length + minimumSize - 1;

minHeight = minWidth = minimumSize;
maxHeight = maxWidth = maximumSize;


const costPerInch = (size, costPerSize = getDataById(['cost-per-inch'], productClData)[0].options) => {

    if (size < 8) {
        return false;
    }
    else if (size > 45) {
        return false;
    }

    const sizeInch = parseInt(size);

    const foundItem = costPerSize.find(item => item[`${sizeInch} Inch`] !== undefined);

    if (foundItem) {
        const cost = parseFloat(foundItem[`${sizeInch} Inch`]);
        return parseFloat(cost);

    } else {
        return 0;
    }


};
let updateArrowLine = (height, width, item) => {
    if (selectedNode.getClassName() !== 'Line') return;
    let points = selectedNode.points();
    let pointA = [points[0], points[1]];
    let pointB = [points[2], points[3]];
    let pointC = [points[4], points[5]];
    let pointD = [points[6], points[7]];
    let pointE = [points[8], points[9]];
    let pointF = [points[10], points[11]];
    let pointG = [points[12], points[13]];
    let posX = points[0];
    let posY = points[1];

    if (height == null) {
        height = selectedNode.height() * selectedNode.scaleY();
    }

    if (width == null) {
        width = selectedNode.width() * selectedNode.scaleX();
    }

    if (item == 'width') {
        if (width !== null) {
            pointB = [posX + (width / 2), posY]
            pointC = [posX + (width / 2), posY - (height / 3)]
            pointD = [pointB[0] + (width / 2), pointC[1] + (height / 2)]
            pointE = [pointB[0], pointC[1] + height]
            pointF = [pointE[0], pointE[1] - (height / 3)];
            pointG = [pointA[0], pointF[1]]
        }
    }

    if (item == 'height') {
        if (width !== null) {
            pointB = [posX + (width / 2), posY]
            pointC = [posX + (width / 2), posY - (height / 3)]
            pointD = [pointB[0] + (width / 2), pointC[1] + (height / 2)]
            pointE = [pointB[0], pointC[1] + height]
            pointF = [pointE[0], pointE[1] - (height / 3)];
            pointG = [pointA[0], pointF[1]]
        }
    }

    let updatedPoints = [...pointA, ...pointB, ...pointC, ...pointD, ...pointE, ...pointF, ...pointG];


    return updatedPoints;
}


let getElementById = (node) => {
    let nodeId = 0;

    if (Number.isInteger(node)) {
        nodeId = node
    } else {
        nodeId = node.id
    }
    let currentStage = store.getState()
    elementsNodes = currentStage.elements || 0;
    if (elementsNodes.length < 1) return;
    for (let i = 0; i < elementsNodes.length; i++) {

        let listId = elementsNodes[i].id;

        if (nodeId == listId) {
            return elementsNodes[i];
        }
    }

}

function isValidLink(str) {
    const pattern = new RegExp('^(https?:\\/\\/)?' + // protocol
        '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.?)+[a-z]{2,}|' + // domain name
        '((\\d{1,3}\\.){3}\\d{1,3}))' + // OR ip (v4) address
        '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*' + // port and path
        '(\\?[;&a-z\\d%_.~+=-]*)?' + // query string
        '(\\#[-a-z\\d_]*)?$', 'i'); // fragment locator
    return !!pattern.test(str);
}



function pxToIn(px) {
    let inch = px / ppi;
    return inch.toFixed(1)
}

function getTextDimensions(node,) {
    const textWidthInPx = ((node.width() * node.scaleX()));
    const textHeightInPx = ((node.height() * node.scaleY()));

    switch (node.getClassName()) {

        case 'RegularPolygon':
            let triHeight = ((node.radius() * 2) * node.scaleY()) / triangleReduction;
            let triWidth = ((node.radius() * 2) * node.scaleX()) / triangleReduction
            return `H:${pxToIn(triHeight)} x W:${pxToIn(triWidth)}`;
            break;
        case 'Line':
            let arrowHeightInch = ((node.points()[9] - node.points()[5]) / dpi) * node.scaleY()
            let arrowWidthInch = ((node.points()[6] - node.points()[0]) / dpi) * node.scaleX()

            return `H:${arrowHeightInch.toFixed(1)} x W:${arrowWidthInch.toFixed(1)}`;

            break;

        default:
            return `H:${pxToIn(textHeightInPx)} x W:${pxToIn(textWidthInPx)}`;

            break;
    }
}

function maintainAspectRatio(newWidth = null, newHeight = null) {

    let defaultHeight = selectedNode.height() * selectedNode.scaleY()
    let defaultWidth = selectedNode.width() * selectedNode.scaleX()
    // Calculate the aspect ratio
    const aspectRatio = defaultWidth / defaultHeight;

    // If a new width is provided, calculate the new height
    if (newWidth !== null) {
        return {
            width: newWidth,
            height: newWidth / aspectRatio
        };
    }

    // If a new height is provided, calculate the new width
    if (newHeight !== null) {
        return {
            width: newHeight * aspectRatio,
            height: newHeight
        };
    }

    // If neither new width nor height is provided, return the default dimensions
    return {
        width: defaultWidth,
        height: defaultHeight
    };
}


function saveState() {
    const json = stage.toJSON();
    undoStack.push(json);
    redoStack.length = 0; // clear redo stack
}

function loadState(json) {
    stage.destroyChildren();
    Konva.Node.create(json, 'container');
    selectedNode = null;
    selectedNodeType = null;
    stage.find('Transformer').destroy();
    layer.draw();
}


function updateBottombarOverlay() {

    let overlayContainer = document.querySelector('.editor-bottombar .bottom-left .bottombar-overlay')
    if (nodeLists.length > 0) {
        overlayContainer.style.display = 'none'
    } else {
        overlayContainer.style.display = 'block';

    }
}

function drawWidthArrows(startY, textValue) {

    let middleX = (stage.width() * stage.scaleX()) / 2;
    let arrowWidth = stage.width() / 3;

    let widthArrow = new Konva.Group({
        draggable: false
    })

    const leftArrow = new Konva.Arrow({
        points: [middleX - 40, startY, middleX - arrowWidth, startY],
        pointerLength: 10,
        pointerWidth: 10,
        fill: 'black',
        stroke: 'black',
        strokeWidth: 2,
    });

    widthArrow.add(leftArrow)


    // Points for right arrow
    const rightArrow = new Konva.Arrow({
        points: [middleX + 40, startY, middleX + arrowWidth, startY],
        pointerLength: 10,
        pointerWidth: 10,
        fill: 'black',
        stroke: 'black',
        strokeWidth: 2,
    });

    const widthInInch = new Konva.Text({
        x: middleX - 20,
        y: startY - 10,
        text: textValue,
        fontSize: 20,
        fontFamily: 'Arial',
        fill: 'black',
    });


    widthArrow.add(rightArrow)
    widthArrow.add(widthInInch)
    layer.add(widthArrow);
    widhtDisplay = widthInInch

    widthArrows = widthArrow

    layer.draw();
}

function drawHeightArrows(startX, textValue = 0) {


    let middleY = stage.height() / 2;
    let arrowHeight = stage.height() / 3;

    let heightArrow = new Konva.Group({
        draggable: false
    })

    const topArrow = new Konva.Arrow({
        points: [startX, middleY - 20, startX, middleY - arrowHeight],
        pointerLength: 10,
        pointerWidth: 10,
        fill: 'black',
        stroke: 'black',
        strokeWidth: 2,
    });

    heightArrow.add(topArrow);


    // Points for right arrow
    const bottomArrow = new Konva.Arrow({
        points: [startX, middleY + 20, startX, middleY + arrowHeight],
        pointerLength: 10,
        pointerWidth: 10,
        fill: 'black',
        stroke: 'black',
        strokeWidth: 2,
    });

    heightArrow.add(bottomArrow)

    const heightInInch = new Konva.Text({
        x: startX - 20,
        y: middleY - 10,
        text: textValue,
        fontSize: 20,
        fontFamily: 'Arial',
        fill: 'black',
    });

    heightArrow.add(heightInInch)



    heightDisplay = heightInInch;
    heightArrows = heightArrow;

    layer.add(heightArrow);
    layer.draw();
}



let updatePreview = (type = null, node = null) => {

    if(selectedNode == null) {
        elementDimenstionContainer.parentElement.style.setProperty('display', 'none','important')

    }else {
        elementDimenstionContainer.parentElement.style.setProperty('display', 'flex','important')

    }
    for (let i = 0; i < previewNodeLists.length; i++) {
        previewNodeLists[i].destroy();
        previewLayer.batchDraw();
    }

    if (!type && !node) {
        previewNoItemText();
        return;
    }

    let strokeWidth = activeTrimcapSizeCode / 1.5
    let previewNode = null;
    let previewNodeWidth = 150;
    let previewNodeHeight = previewNodeWidth * (node.height() / node.width());
    const shapeConfig = {
        draggable: false,
        fill: activeFaceCode,
        stroke: activeTrimcapColorCode,
        strokeWidth: strokeWidth,
        shadowColor: activeReturnColorCode,
        shadowOffsetX: activeReturnSizeCode / 1.5,
        shadowOffsetY: activeReturnSizeCode / 1.5,
        shadowBlur: activeReturnSizeCode / 1.5,
        cornerRadius: selectedNode ? selectedNode.attrs.cornerRadius / 2 : 0
    };


    switch (type) {
        case 'text':
            previewNode = new Konva.Text({
                text: node.text() || selectedNode.text(),
                fontFamily: activeFontCode,
                fill: activeFaceCode,
                stroke: activeTrimcapColorCode,
                strokeWidth: strokeWidth,
                shadowColor: activeReturnColorCode,
                shadowOffsetX: activeReturnSizeCode / 1.5,
                shadowOffsetY: activeReturnSizeCode / 1.5,
                shadowBlur: activeReturnSizeCode / 1.5,
                fontSize: 50
            })
            previewNode.y((previewStage.height() / 2) - (previewNode.height() / 2));
            previewNode.x((previewStage.width() / 2) - (previewNode.width() / 2));
            if (previewNode.text().length > 7) {
                previewNode.setAttrs({
                    fontSize: (50 - (previewNode.text().length * 1.7)),
                    x: 10,
                    y: ((previewStage.height() / 2) - (previewNode.height() / 2))
                })
            }
            previewLayer.batchDraw();
            break;

        case 'rect':

            previewNode = new Konva.Rect({
                ...shapeConfig,
            })
            previewNode.setAttrs({
                height: previewNodeHeight,
                width: previewNodeWidth,
            });

            previewNode.x((previewStage.width() / 2) - (previewNode.width() / 2));
            previewNode.y(((previewStage.height() / 2) - (previewNode.height() / 2)));
            previewLayer.batchDraw();

            break;

        case 'circle':
            previewNode = new Konva.Circle({
                ...shapeConfig,
                radius: 75,
                x: 100,
                y: 100
            })

            previewLayer.batchDraw();

            // previewNode.x((previewStage.width() / 2) - (previewNode.width() / 2));
            // previewNode.y(((previewStage.height() / 2) - (previewNode.height() / 2)));

            break;

        case 'arrow':
            var points = [
                37.5, 67,  // Point A
                112.5, 67, // Point B
                112.5, 33.5, // Point C
                187.5, 83.75, // Point D (tip of the arrow)
                112.5, 134, // Point E
                112.5, 100.5, // Point F
                37.5, 100.5  // Point G
            ];

            // Find the bounding box of the points
            var minX = Math.min(...points.filter((_, i) => i % 2 === 0));
            var maxX = Math.max(...points.filter((_, i) => i % 2 === 0));
            var minY = Math.min(...points.filter((_, i) => i % 2 === 1));
            var maxY = Math.max(...points.filter((_, i) => i % 2 === 1));

            // Calculate the center of the arrow
            var arrowCenterX = (minX + maxX) / 2;
            var arrowCenterY = (minY + maxY) / 2;

            // Calculate the center of the stage
            var stageCenterX = previewStage.width() / 3;
            var stageCenterY = previewStage.height() / 4;

            // Calculate the offset to move the arrow to the center of the stage
            var offsetX = stageCenterX - arrowCenterX;
            var offsetY = stageCenterY - arrowCenterY;

            // Apply the offset to all points
            var centeredPoints = points.map((value, index) => {
                if (index % 2 === 0) {
                    return value + offsetX;  // Adjust x-coordinates
                } else {
                    return value + offsetY;  // Adjust y-coordinates
                }
            });

            previewNode = new Konva.Line({
                points: centeredPoints,
                fill: shapeConfig.fill,
                stroke: shapeConfig.stroke,
                strokeWidth: strokeWidth,
                closed: true,
                shadowColor: shapeConfig.shadowColor,
                shadowBlur: shapeConfig.shadowBlur,
                shadowOffset: { x: shapeConfig.shadowOffsetX, y: shapeConfig.shadowOffsetY },
                draggable: false,
            });


            previewNode.x((previewStage.width() / 2) - (previewNode.width() / 2));
            previewNode.y(((previewStage.height() / 2) - (previewNode.height() / 2)));
            previewLayer.batchDraw();

            break;

        case 'triangle':
            previewNode = new Konva.RegularPolygon({
                ...shapeConfig,
                sides: 3,
                radius: 66,
                x: 100,
                y: 100
            });
            previewnType = 'triangle';

            previewLayer.batchDraw();

            break;
        case 'star':
            previewNode = new Konva.Star({
                ...shapeConfig,
                numPoints: 5,
                innerRadius: 30,
                outerRadius: 50,
                x: 100,
                y: 100
            })


            previewLayer.batchDraw();

            break;
        case 'raceway':

            previewNode = new Konva.Rect({
                ...shapeConfig,
                fill: 'gray',
                height: 80,
                width: previewCanvasWidth - 20,
                opacity: 0.2,
                stroke: 'gray',
                shadowColor: 'gray',
            })


            let racewayText = new Konva.Text({
                x: (previewNode.width / 2),
                y: (previewNode.height / 2),
                text: 'Raceway',
                fill: 'gray',
                fontSize: 25
            })

            previewLayer.add(racewayText);

            previewNodeLists.push(racewayText);

            previewNode.x((previewStage.width() / 2) - (previewNode.width() / 2));
            previewNode.y(((previewStage.height() / 2) - (previewNode.height() / 2)));
            layer.draw();
            racewayText.x((previewNode.width() / 2) - (racewayText.width() / 2) + 10)
            racewayText.y(previewNode.y() + (previewNode.height() / 2) - (racewayText.height() / 2))

            previewLayer.batchDraw();
            break;



        default:


            previewNode = node.clone({
                ...shapeConfig,
            });

            previewNode.setAttrs({
                height: previewNodeHeight,
                width:  previewNodeWidth,
                radius: 50
            });
            previewNode.x((previewStage.width() / 2) - (previewNode.width() / 2));
            previewNode.y(((previewStage.height() / 2) - (previewNode.height() / 2)));
            break;
    }

    currentPreviewNode = previewNode;




    previewLayer.add(previewNode);
    previewNodeLists.push(previewNode);

}





// update text node

function updateNode(sNode, meta = null) {

    if (sNode == null) return;
    switch (selectedNodeType) {
        case 'Text':

            // Update text content
            if (sNode.value !== undefined && meta == 'text') {
                sNode.text(textInput.value);
            }

            if (fontFamily !== undefined && meta == 'font-family') {
                sNode.fontFamily(fontFamily);
                sNode.getLayer().batchDraw();
                triggerTransformEvent()

                sNode.fontSize(selectedNode.fontSize() + 0.1);
                sNode.fontSize(selectedNode.fontSize() - 0.1);

                triggerTransformEvent();
                sNode.width(undefined)
                layer.draw();
            }

            // Update font size
            if (fontSize !== undefined && meta == 'font-size') {
                sNode.fontSize(fontSize);
            }

            // Update fill color (face color)
            if (faceColor !== undefined && meta == 'face-color') {
                sNode.fill(faceColor);
            }

            // Update stroke color
            if (trimCapColor !== undefined && meta == 'trimcap-color') {
                sNode.stroke(trimCapColor);
            }

            // Update shadow color
            if (returnColor !== undefined && meta == 'return-color') {
                sNode.shadowColor(returnColor);
            }

            // Update shadow size
            if (returnSize !== undefined && meta == 'return-size') {
                sNode.shadowOffsetX(returnSize);
                sNode.shadowOffsetY(returnSize);
                sNode.shadowBlur(returnSize);
            }
            updatePreview('text', sNode);

            // Redraw layer to apply changes
            sNode.getLayer().batchDraw();
            break;
        case 'Rect':


            // Update fill color (face color)
            if (faceColor !== undefined && meta == 'face-color') {
                sNode.fill(faceColor);
            }

            if (trimCapColor !== undefined && meta == 'trimcap-color') {
                sNode.stroke(trimCapColor);
            }

            if (returnColor !== undefined && meta == 'return-color') {
                sNode.shadowColor(returnColor);
            }
            if (returnSize !== undefined && meta == 'return-size') {
                sNode.shadowOffsetX(returnSize);
                sNode.shadowOffsetY(returnSize);
                sNode.shadowBlur(returnSize);
            }
            break;
        case 'Circle':

            if (faceColor !== undefined && meta == 'face-color') {
                sNode.fill(faceColor);
            }

            if (trimCapColor !== undefined && meta == 'trimcap-color') {
                sNode.stroke(trimCapColor);
            }

            if (returnColor !== undefined && meta == 'return-color') {
                sNode.shadowColor(returnColor);
            }
            if (returnSize !== undefined && meta == 'return-size') {
                sNode.shadowOffsetX(returnSize);
                sNode.shadowOffsetY(returnSize);
                sNode.shadowBlur(returnSize);
            }
            break;
        case 'RegularPolygon':

            if (faceColor !== undefined && meta == 'face-color') {
                sNode.fill(faceColor);
            }

            if (trimCapColor !== undefined && meta == 'trimcap-color') {
                sNode.stroke(trimCapColor);
            }

            if (returnColor !== undefined && meta == 'return-color') {
                sNode.shadowColor(returnColor);
            }
            if (returnSize !== undefined && meta == 'return-size') {
                sNode.shadowOffsetX(returnSize);
                sNode.shadowOffsetY(returnSize);
                sNode.shadowBlur(returnSize);
            }
            break;
        case 'Star':


            if (faceColor !== undefined && meta == 'face-color') {
                sNode.fill(faceColor);
            }

            if (trimCapColor !== undefined && meta == 'trimcap-color') {
                sNode.stroke(trimCapColor);
            }

            if (returnColor !== undefined && meta == 'return-color') {
                sNode.shadowColor(returnColor);
            }
            if (returnSize !== undefined && meta == 'return-size') {
                sNode.shadowOffsetX(returnSize);
                sNode.shadowOffsetY(returnSize);
                sNode.shadowBlur(returnSize);
            }
            break;
        case 'Line':

            // Update fill color (face color)
            if (faceColor !== undefined && meta == 'face-color') {
                sNode.fill(faceColor);
            }

            if (trimCapColor !== undefined && meta == 'trimcap-color') {
                sNode.stroke(trimCapColor);
            }

            if (returnColor !== undefined && meta == 'return-color') {
                sNode.shadowColor(returnColor);
            }
            if (returnSize !== undefined && meta == 'return-size') {
                sNode.shadowOffsetX(returnSize);
                sNode.shadowOffsetY(returnSize);
                sNode.shadowBlur(returnSize);
            }
            break;
    }

    let tr = sNode.getAttr('transformer');
    tr.update();

    layer.batchDraw();

}


// Function to zoom the stage with center focus
function zoomStage(scaleFactor) {
    var oldScaleX = stage.scaleX();
    var oldScaleY = stage.scaleY();

    // Calculate the new scale
    var newScaleX = oldScaleX * scaleFactor;
    var newScaleY = oldScaleY * scaleFactor;

    var oldPos = stage.position();


    // Apply the new scale and position to the stage
    stage.scale({ x: newScaleX, y: newScaleY });

    // Get the center of the viewport
    var center = {
        x: stage.width() / 2,
        y: stage.height() / 2
    };

    // Calculate the new position of the stage to keep the center fixed
    var newPos = {
        x: center.x - (center.x - oldPos.x) * (newScaleX / oldScaleX),
        y: center.y - (center.y - oldPos.y) * (newScaleY / oldScaleY)
    };


    //stage.position(newPos);
    stage.batchDraw();
    var stageCenterX = (stage.width() * stage.scaleX()) / 2;
    var stageCenterY = (stage.height() * stage.scaleY()) / 2;



    background.scale({
        x: 1 / newScaleX,
        y: 1 / newScaleY,
    })

    if (heightArrows != null) {
        heightArrows.scale({
            x: 1 / newScaleX,
            y: 1 / newScaleY,
        })
        let currentHeightPos = heightArrows.position()

        // heightArrows.x(20 * newScaleX)
        // heightArrows.y(20 * newScaleY)
    }
    if (widthArrows != null) {
        widthArrows.scale({
            x: 1 / newScaleX,
            y: 1 / newScaleY,
        })

    }

    nodeLists.forEach(function (nodeObject, key) {
        let node = nodeObject.node;
        let transformer = node.getAttr('transformer')

        if (transformer) {
            transformer.update()
        }
    })

    layer.draw();

    updateHeightWidthDisplay()

}

function triggerTransformEvent() {
    if (selectedNode == null) {
        return;
    }
    // Update the rectangle's scale (just for demonstration)
    selectedNode.scaleX(1);
    selectedNode.scaleY(1);

    let transformer = selectedNode.getAttr('transformer')

    layer.add(transformer)
    transformer.nodes([selectedNode]);
    selectedNode.fire('transform', { type: 'transform' });
    transformer.update();
    layer.batchDraw();

    if (selectedNode.getClassName() == 'Text') {
    }

}

function updateHeightWidthInput(height, width, type) {
    let nodeHeight = height ? parseFloat(height) : false;
    let nodeWidth = width ? parseFloat(width) : false;
    switch (type) {
        case 'text':

            if (nodeHeight) {
                sizeHeightInput.value = parseFloat(pxToIn(nodeHeight)).toFixed(1);
                sizeHeightInput.removeAttribute('readonly')
            }
            if (nodeWidth) {

                if (typeof nodeWidth == 'number') {
                    sizeWidthInput.value = parseFloat(pxToIn(nodeWidth)).toFixed(1);

                }
            }


            break;
        case 'raceway':

            if (nodeWidth) {
                sizeWidthInput.value = nodeWidth;
                sizeHeightInput.setAttribute('readonly', 'true');
                if (typeof width == 'number') {
                    sizeWidthInput.value = parseFloat(pxToIn(nodeWidth)).toFixed(1);

                }

                if (!height) {
                    sizeHeightInput.value = 8

                } else {
                    sizeHeightInput.value = nodeHeight;
                }
            }


            break;

        case 'arrow':
            //sizeWidthInput.setAttribute('readonly', 'true');
            //sizeHeightInput.setAttribute('readonly', 'true');

            //sizeHeightInput.setAttribute('readonly', 'true');
            if (typeof width == 'number') {
                if (nodeWidth) {
                    sizeWidthInput.value = parseFloat(pxToIn(nodeWidth)).toFixed(1);

                }

                if (nodeWidth) {
                    sizeHeightInput.value = parseFloat(pxToIn(nodeHeight)).toFixed(1);

                }
                //sizeWidthInput.value = parseFloat(pxToIn(nodeWidth)).toFixed(1);

            }

            break;
        default:
            //sizeHeightInput.value = nodeHeight * dpi;
            sizeHeightInput.removeAttribute('readonly')
            sizeWidthInput.removeAttribute('readonly');

            if (typeof width == 'number') {
                sizeWidthInput.value = parseFloat(pxToIn(nodeWidth)).toFixed(1);

            }
            if (typeof nodeHeight == 'number') {
                sizeHeightInput.value = parseFloat(pxToIn(nodeHeight)).toFixed(1);

            }


            break;
    }

}

function updateHeightWidthDisplay() {

    if (nodeLists.length < 1) {

        widhtDisplay.text(`0"`)
        heightDisplay.text(`0"`);
        return;
    }

    let stageScaleX = stage.scaleX();
    let stageScaleY = stage.scaleY();

    let updatedHeight = nodeLists[0].node.height() * nodeLists[0].node.scaleY()
    let updatedWidth = nodeLists[0].node.width() * nodeLists[0].node.scaleX();


    let minLeft = 0;
    let minRight = 0;
    let minTop = 0;
    let minBottom = 0;


    let firstNode = nodeLists[0];
    switch (firstNode.node.getClassName()) {

        case 'RegularPolygon':
            minLeft = nodeLists[0].node.x() - ((firstNode.node.radius() * firstNode.node.scaleX()) / triangleReduction);
            minRight = nodeLists[0].node.x() + ((firstNode.node.radius() * firstNode.node.scaleX()) / triangleReduction);

            // minTop = nodeLists[0].node.y() - ((firstNode.node.radius() * firstNode.node.scaleY()) / triangleReduction)
            minTop = (nodeLists[0].node.y() - ((firstNode.node.height() * firstNode.node.scaleY()) / 2)) / triangleReduction

            minBottom = (nodeLists[0].node.y() + ((firstNode.node.height() * firstNode.node.scaleY()) / 2)) / triangleReduction


            // minBottom = nodeLists[0].node.x() + ((firstNode.node.radius() * firstNode.node.scaleY()) / triangleReduction);
            break;

        case 'Star':
            let starWidth = firstNode.node.width() * firstNode.node.scaleX()
            let starHeight = firstNode.node.height() * firstNode.node.scaleY();

            minLeft = nodeLists[0].node.x() - (starWidth / 2);
            minRight = nodeLists[0].node.x() + (starWidth / 2);
            minTop = nodeLists[0].node.y() - (starHeight / 2);
            minBottom = nodeLists[0].node.y() + (starHeight / 2);

            break;

        case 'Line':
            let arrowPoints = firstNode.node.points();

            let arrowWidth = arrowPoints[6] - arrowPoints[0]
            let arrowHeight = arrowPoints[9] - arrowPoints[5]

            minLeft = arrowPoints[0] + firstNode.node.x();
            minRight = arrowPoints[6] + firstNode.node.x();
            minTop = arrowPoints[5] + firstNode.node.y();
            minBottom = arrowPoints[9] + firstNode.node.y();

            break;

        case 'Circle':
            let circleWidth = firstNode.node.width() * firstNode.node.scaleX()
            let circleHeight = firstNode.node.height() * firstNode.node.scaleY();

            minLeft = firstNode.node.x() - (circleWidth / 2);
            minTop = firstNode.node.y() - (circleHeight / 2);
            minRight = firstNode.node.x() + (circleWidth / 2);
            minBottom = firstNode.node.y() + (circleHeight / 2);


            break;

        default:

            minLeft = nodeLists[0].node.x();
            minRight = nodeLists[0].node.x() + (updatedWidth);
            minTop = nodeLists[0].node.y();
            minBottom = nodeLists[0].node.y() + (updatedHeight);

            break;
    }


    nodeLists.forEach(singleNode => {

        let nodeLeft = 0;
        let nodeTop = 0;
        let nodeRight = 0;
        let nodeBottom = 0;





        switch (singleNode.node.getClassName()) {
            case 'RegularPolygon':

                nodeLeft = singleNode.node.x() - ((singleNode.node.radius() * singleNode.node.scaleX()) / triangleReduction);
                nodeRight = singleNode.node.x() + ((singleNode.node.radius() * singleNode.node.scaleX()) / triangleReduction);
                nodeTop = nodeLists[0].node.y() - (((firstNode.node.height() * firstNode.node.scaleY()) / 2)) / triangleReduction
                nodeBottom = (nodeLists[0].node.y() + (((firstNode.node.height() * firstNode.node.scaleY()) / 2))) / triangleReduction
                break;

            case 'Star':
                let starWidth = singleNode.node.width() * singleNode.node.scaleX()
                let starHeight = singleNode.node.height() * singleNode.node.scaleY();

                nodeLeft = singleNode.node.x() - (starWidth / 2);
                nodeTop = singleNode.node.y() - (starHeight / 2);
                nodeRight = singleNode.node.x() + (starWidth / 2);
                nodeBottom = singleNode.node.y() + (starHeight / 2);

                break;
            case 'Line':

                let arrowPoints = singleNode.node.points();



                let arrowWidth = arrowPoints[6] - arrowPoints[0]
                let arrowHeight = arrowPoints[9] - arrowPoints[5]

                nodeLeft = arrowPoints[0] + singleNode.node.x();
                nodeRight = arrowPoints[6] + singleNode.node.x();
                nodeTop = arrowPoints[5] + singleNode.node.y();
                nodeBottom = arrowPoints[9] + singleNode.node.y();

                break;

            case 'Circle':
                let circleWidth = singleNode.node.width() * singleNode.node.scaleX()
                let circleHeight = singleNode.node.height() * singleNode.node.scaleY();

                nodeLeft = singleNode.node.x() - (circleWidth / 2);
                nodeTop = singleNode.node.y() - (circleHeight / 2);
                nodeRight = singleNode.node.x() + (circleWidth / 2);
                nodeBottom = singleNode.node.y() + (circleHeight / 2);


                break;

            default:
                nodeLeft = singleNode.node.x();
                nodeTop = singleNode.node.y();
                nodeRight = (singleNode.node.x() + (singleNode.node.width() * singleNode.node.scaleX()));
                nodeBottom = singleNode.node.y() + (singleNode.node.height() * singleNode.node.scaleY());
                break;
        }



        if (nodeLeft < minLeft) {
            minLeft = nodeLeft;
        }

        if (nodeRight > minRight) {
            minRight = nodeRight
        }

        if (nodeTop < minTop) {
            minTop = nodeTop;
        }

        if (nodeBottom > minBottom) {
            minBottom = nodeBottom;
        }

        contentHeight = (minBottom - minTop) / dpi || 0;
        contentWidth = (minRight - minLeft) / dpi || 0;



        let topArrow = heightArrows.children[0];
        let bottomArrow = heightArrows.children[1];

        let leftArrow = widthArrows.children[0];
        let rightArrow = widthArrows.children[1];

        let xArrowHeight = ((contentHeight * dpi) / 2) * stageScaleY;
        let xArrowWidth = ((contentWidth * dpi) / 2) * stageScaleX;

        let middleY = ((minTop * stageScaleY) + xArrowHeight);
        let middleX = (minLeft * stageScaleX) + xArrowWidth;

        //[startX, middleY - 20, startX, middleY - arrowHeight]

        topArrow.points([20, middleY - 15, 20, middleY - xArrowHeight])
        bottomArrow.points([20, middleY + 15, 20, middleY + xArrowHeight])
        heightDisplay.y(middleY - 10)

        leftArrow.points([middleX - 35, 20, middleX - xArrowWidth, 20])
        rightArrow.points([middleX + 35, 20, middleX + xArrowWidth, 20])
        widhtDisplay.x(middleX - 25)

        layer.draw()

        widhtDisplay.text(`${contentWidth.toFixed(1)}"`);
        heightDisplay.text(`${contentHeight.toFixed(1)}"`);

    })
}


function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function removeClassFromSiblings(element, className) {

    let sliderChooseItemContainers = document.querySelectorAll('.slider-choose-item-container.have-background-color');

    if (sliderChooseItemContainers.length > 0) {
        sliderChooseItemContainers.forEach(container => {
            [...container.children].forEach(e => {
                e.classList.remove(className);
            })

        })
    } else {
        const siblings = Array.from(element.parentNode.children);

        siblings.forEach(sibling => {
            if (sibling !== element) {
                sibling.classList.remove(className);
            }
        });
    }



}

let handleChooseItemClick = (slideChooseItem, slideChooseContainer, actionId) => {
    removeClassFromSiblings(slideChooseContainer, 'active');
    slideChooseItem.classList.add('active');

    let itemType = slideChooseItem.dataset.type;
    let itemValue = slideChooseItem.dataset.value;
    let itemName = slideChooseItem.dataset.name;
    let itemCost = slideChooseItem.dataset.cost;
    if (selectedNode == null) {
        return;
    }

    let changeEvent = new CustomEvent('change', {
        bubbles: true,
        cancelable: true
    })

    let itemSelector = document.querySelector('.select-' + itemType);
    itemSelector.dataset.active = itemName + '/' + itemValue;

    switch (actionId) {
        case 'font':
            fontFamily = itemValue;
            activeFontCode = itemValue;
            activeFontTitle = itemName;
            activeFontTitle
            updateNode(selectedNode, 'font-family');
            triggerTransformEvent();

            setTimeout(() => {
                updateNode(selectedNode, 'font-family');
                selectedNode.scaleX(selectedNode.scaleX());
                triggerTransformEvent();
            }, 1000);

            store.dispatch({
                type: 'UPDATE_ELEMENT',
                payload: {
                    id: selectedNode._id,
                    font: {
                        title: itemName,
                        code: itemValue,
                    }
                }
            })

            triggerTransformEvent();
            setTextFSI(selectedNode._id, 'yes')
            document.querySelector('.select-' + itemType + ' .name').innerHTML = itemName;
            break;

        case 'face-color':
            document.querySelector('.select-' + itemType + ' .name').innerHTML = itemName;
            faceColorPicker.value = itemValue;
            faceColorPicker.dispatchEvent(changeEvent);
            colorCost = itemCost;
            faceColor = itemValue
            activeFaceTitle = itemName
            activeFaceCode = itemValue


            let sameReturnColorData = null;

            if (isReturnColorSame) {
                activeReturnColorCode = itemValue;
                activeReturnColorTitle = itemName;
                sameReturnColorData = {
                    code: itemValue,
                    title: itemName
                }
            }

            if (itemCost) {

                switch (selectedNode.getClassName()) {
                    case 'Text':

                        let totalLength = ((selectedNode.text()).replace(' ', '')).length;
                        let singleHeightInch = (selectedNode.height() * selectedNode.scaleY()) / dpi;
                        let totalTextHeightInch = singleHeightInch * totalLength
                        totalColorCost = totalTextHeightInch * itemCost;
                        store.dispatch({
                            type: 'UPDATE_ELEMENT',
                            payload: {
                                id: selectedNode._id,
                                width: pxToIn(selectedNode.width() * selectedNode.scaleX()),
                                height: singleHeightInch,
                                cost: (costPerInch(singleHeightInch)) * totalLength,
                                text: selectedNode.text(),
                                colorCost: totalColorCost,
                                faceColor: {
                                    title: itemName,
                                    code: itemValue
                                },
                                returnColor: sameReturnColorData,
                                faceCostPerInch: itemCost
                            }
                        })
                        break;
                    case 'RegularPolygon':


                        let totalTriWidthInch = ((selectedNode.width() * selectedNode.scaleX()) / dpi) / triangleReduction;
                        let totalTriHeightInch = ((selectedNode.height() * selectedNode.scaleY()) / dpi) / triangleReduction;
                        let totalTriCost = (costPerInch(totalTriWidthInch))
                        totalColorCost = totalTriWidthInch * itemCost;

                        if (totalTriWidthInch < totalTriHeightInch) {
                            totalTriCost = (costPerInch(totalTriHeightInch))
                            totalColorCost = totalTriHeightInch * itemCost;
                        }
                        store.dispatch({
                            type: 'UPDATE_ELEMENT',
                            payload: {
                                id: selectedNode._id,
                                width: totalTriWidthInch,
                                height: totalTriHeightInch,
                                cost: totalTriCost,
                                colorCost: totalColorCost,
                                faceColor: {
                                    title: itemName,
                                    code: itemValue
                                },
                                returnColor: sameReturnColorData,
                                faceCostPerInch: itemCost


                            }
                        })
                        break;

                    default:


                        let totalWidthInch = (selectedNode.width() * selectedNode.scaleX()) / dpi;
                        let totalHeightInch = (selectedNode.height() * selectedNode.scaleY()) / dpi;
                        let totalCost = costPerInch(totalWidthInch);
                        totalColorCost = totalWidthInch * itemCost;
                        if (totalHeightInch > totalWidthInch) {
                            totalCost = costPerInch(totalHeightInch);
                            totalColorCost = totalHeightInch * itemCost;
                        }

                        store.dispatch({
                            type: 'UPDATE_ELEMENT',
                            payload: {
                                id: selectedNode._id,
                                width: totalWidthInch,
                                height: totalHeightInch,
                                cost: totalCost,
                                colorCost: totalColorCost,
                                faceColor: {
                                    title: itemName,
                                    code: itemValue
                                },
                                returnColor: sameReturnColorData,

                                faceCostPerInch: itemCost
                            }
                        })
                        break;
                }
            }

            if (isReturnColorSame) {
                if (itemValue != 'same.face') {
                    returnColorPicker.value = itemValue;
                    returnColorPicker.dispatchEvent(changeEvent);

                    activeReturnColorTitle = itemName
                    activeReturnColorCode = itemValue
                    returnColor = itemValue
                    updateActiveItem('return', `${itemName}/${itemValue}`)

                }


            }

            break;
        case 'return-color':

            if (isReturnColorSame) return;
            document.querySelector('.select-' + itemType + ' .name').innerHTML = itemName;
            returnColorPicker.value = itemValue;
            returnColorPicker.dispatchEvent(changeEvent);
            itemSelector.dataset.active = itemName + '/' + itemValue;

            activeReturnColorTitle = itemName
            activeReturnColorCode = itemValue
            store.dispatch({
                type: 'UPDATE_ELEMENT',
                payload: {
                    id: selectedNode._id,
                    returnColor: {
                        title: itemName,
                        code: itemValue
                    }
                }
            })

            break;
        case 'return-size':
            //document.querySelector('.select-' + itemType + ' .name').innerHTML = itemName;
            returnSizeInput.value = itemValue;
            returnSizeInput.dispatchEvent(changeEvent);
            itemSelector.dataset.activeSecond = itemName + '/' + itemValue;
            activeReturnSizeCode = itemValue;
            activeReturnSizeTitle = itemName;
            updatePreview('text',selectedNode)


            store.dispatch({
                type: 'UPDATE_ELEMENT',
                payload: {
                    id: selectedNode._id,
                    returnSize: {
                        title: itemName,
                        code: itemValue
                    }
                }
            })
            break;
        case 'trimcap-color':
            document.querySelector('.select-' + itemType + ' .name').innerHTML = itemName;
            trimcapColorPicker.value = itemValue;
            trimcapColorPicker.dispatchEvent(changeEvent);

            activeTrimcapColorTitle = itemName;
            activeTrimcapColorCode = itemValue;
            store.dispatch({
                type: 'UPDATE_ELEMENT',
                payload: {
                    id: selectedNode._id,
                    trimcapColor: {
                        title: itemName,
                        code: itemValue
                    }
                }
            })
            break;

        case 'trimcap-size':
            //document.querySelector('.select-' + itemType + ' .name').innerHTML = itemName;
            trimcapSizeInput.value = itemValue;
            trimcapSizeInput.dispatchEvent(changeEvent);
            itemSelector.dataset.activeSecond = itemName + '/' + itemValue;
            activeTrimcapSizeCode = itemValue;
            activeReturnSizeTitle = itemName;
            updatePreview('text',selectedNode)

            store.dispatch({
                type: 'UPDATE_ELEMENT',
                payload: {
                    id: selectedNode._id,
                    trimcapSize: {
                        title: itemName,
                        code: itemValue
                    }
                }
            })

            break;
    }

    hideLeftSlider();
}

let showLeftSlider = (data, type, column) => {

    if (selectedNode == null) {
        return;
    }
    hideLeftSlider();

    let matchActiveItem = (item) => {
        let itemSelector = document.querySelector('.select-' + type);
        let activeItem = itemSelector.dataset.active;
        let activeItemSecond = itemSelector.dataset.activeSecond;

        for (const itemKey in item) {
            let name = itemKey
            let value = item[itemKey]

            if ((name + '/' + value) == activeItem) {
                return true;
            }
            else if (activeItemSecond) {
                if ((name.replace(/\s+/g, '') + '/' + value.replace(/\s+/g, '')) == activeItemSecond.replace(/\s+/g, '')) {
                    return true;
                } else {
                    return false;
                }
            }



        }




    }



    let leftSliderContainer = document.querySelector('.left-slider-container');
    if (column == 4) {
        leftSliderContainer.classList.add('direction-right');
    }

    leftSliderContainer.replaceChildren();
    let addHeading = (heading, cost) => {

        let headingContainer = document.createElement('div');
        let headingText = document.createElement('h3')

        headingText.classList.add('slider-heading');
        headingContainer.classList.add('heading-container');
        headingText.innerText = heading;
        headingContainer.appendChild(headingText)

        if (cost != '0') {
            let costText = document.createElement('span')
            costText.innerText = cost;
            headingContainer.appendChild(costText)

        }

        leftSliderContainer.appendChild(headingContainer);


    }

    let addChooseItems = (id, items, cost) => {

        let slideChooseContainer = document.createElement('div');
        slideChooseContainer.classList.add('slider-choose-container');

        let slideChooseItemContainerOne = document.createElement('ul');
        slideChooseItemContainerOne.classList.add('slider-choose-item-container');

        let slideChooseItemContainerTwo = document.createElement('ul');
        slideChooseItemContainerTwo.classList.add('slider-choose-item-container');

        let slideChooseItemContainerThree = document.createElement('ul');
        slideChooseItemContainerThree.classList.add('slider-choose-item-container');

        let slideChooseItemContainerFour = document.createElement('ul');
        slideChooseItemContainerFour.classList.add('slider-choose-item-container');

        switch (id) {
            case 'face-color':

                slideChooseItemContainerOne.classList.add('have-background-color');
                slideChooseItemContainerTwo.classList.add('have-background-color');
                slideChooseItemContainerThree.classList.add('have-background-color');
                slideChooseItemContainerFour.classList.add('have-background-color');

                break;
            case 'trimcap-color':

                slideChooseItemContainerOne.classList.add('have-background-color');
                slideChooseItemContainerTwo.classList.add('have-background-color');
                slideChooseItemContainerThree.classList.add('have-background-color');
                slideChooseItemContainerFour.classList.add('have-background-color');

                break;
            case 'return-color':
                if (!isReturnColorSame) {
                    slideChooseItemContainerOne.classList.add('have-background-color');
                }

                slideChooseItemContainerTwo.classList.add('have-background-color');
                slideChooseItemContainerThree.classList.add('have-background-color');
                slideChooseItemContainerFour.classList.add('have-background-color');

                break;
            default:
                slideChooseItemContainerOne.classList.remove('have-background-color');
                slideChooseItemContainerTwo.classList.remove('have-background-color');
                slideChooseItemContainerThree.classList.remove('have-background-color');
                slideChooseItemContainerFour.classList.remove('have-background-color');

                break;
        }

        let itemLength = items.length;
        let itemIndex = 0;
        let currentColumn = 1
        let currentColumnLength = 0
        let itemPerColumn = itemLength > 4 ? Math.round(itemLength / 4) : 1
        if (itemPerColumn) {
            items.forEach(item => {


                for (const itemKey in item) {

                    let name = itemKey
                    let value = item[itemKey]

                    let slideChooseItem = document.createElement('li');
                    slideChooseItem.classList.add('slider-choose-item');
                    if (matchActiveItem(item)) {
                        slideChooseItem.classList.add('active');
                    }


                    slideChooseItem.dataset.cost = cost;
                    slideChooseItem.dataset.type = type;
                    slideChooseItem.dataset.name = name;
                    slideChooseItem.dataset.value = value;

                    if (slideChooseItemContainerOne.classList.contains('have-background-color')) {
                        let chooseColorPrevew = document.createElement('span');
                        let chooseColorName = document.createElement('span');;
                        chooseColorPrevew.classList.add('choose-color-preview')
                        if (isValidLink(value)) {
                            chooseColorPrevew.style.backgroundImage = `url('${value}')`;

                        } else {
                            chooseColorPrevew.style.backgroundColor = value;

                        }
                        chooseColorName.innerText = name;
                        slideChooseItem.appendChild(chooseColorPrevew);
                        slideChooseItem.appendChild(chooseColorName);


                    } else {
                        slideChooseItem.innerHTML = name;

                    }

                    slideChooseItem.addEventListener('click', function (e) {

                        handleChooseItemClick(slideChooseItem, slideChooseItemContainerOne, id);
                        handleChooseItemClick(slideChooseItem, slideChooseItemContainerTwo, id);
                        handleChooseItemClick(slideChooseItem, slideChooseItemContainerThree, id);
                        handleChooseItemClick(slideChooseItem, slideChooseItemContainerFour, id);
                    })

                    switch (currentColumn) {
                        case 1:
                            slideChooseItemContainerOne.appendChild(slideChooseItem);

                            break;

                        case 2:
                            slideChooseItemContainerTwo.appendChild(slideChooseItem);

                            break;

                        case 3:
                            slideChooseItemContainerThree.appendChild(slideChooseItem);

                            break;

                        case 4: slideChooseItemContainerFour.appendChild(slideChooseItem);


                            break;
                    }



                }
                slideChooseContainer.appendChild(slideChooseItemContainerOne)
                slideChooseContainer.appendChild(slideChooseItemContainerTwo)
                slideChooseContainer.appendChild(slideChooseItemContainerThree)
                slideChooseContainer.appendChild(slideChooseItemContainerFour)

                itemIndex++

                if (currentColumnLength == (itemPerColumn)) {
                    currentColumn = currentColumn + 1;
                    currentColumnLength = 0
                }
                currentColumnLength++

            });

            return slideChooseContainer;

        } else {

            items.forEach(item => {


                for (const itemKey in item) {

                    let name = itemKey
                    let value = item[itemKey]

                    let slideChooseItem = document.createElement('li');
                    slideChooseItem.classList.add('slider-choose-item');
                    if (matchActiveItem(item)) {
                        slideChooseItem.classList.add('active');
                    }


                    slideChooseItem.dataset.cost = cost;
                    slideChooseItem.dataset.type = type;
                    slideChooseItem.dataset.name = name;
                    slideChooseItem.dataset.value = value;

                    if (slideChooseItemContainerOne.classList.contains('have-background-color')) {
                        let chooseColorPrevew = document.createElement('span');
                        let chooseColorName = document.createElement('span');;
                        chooseColorPrevew.classList.add('choose-color-preview')
                        if (isValidLink(value)) {
                            chooseColorPrevew.style.backgroundImage = `url('${value}')`;
                        } else {
                            chooseColorPrevew.style.backgroundColor = value;
                        }
                        chooseColorName.innerText = name;
                        slideChooseItem.appendChild(chooseColorPrevew);
                        slideChooseItem.appendChild(chooseColorName);


                    } else {
                        slideChooseItem.innerHTML = name;

                    }

                    slideChooseItemContainerOne.appendChild(slideChooseItem);

                    slideChooseContainer.appendChild(slideChooseItemContainerOne)

                    if (!isReturnColorSame) {
                        slideChooseItem.addEventListener('click', function (e) {
                            handleChooseItemClick(slideChooseItem, slideChooseItemContainerOne, id);
                        })
                    }


                }
            })

            return slideChooseContainer;
        }


    }

    data.forEach(container => {
        let heading = container.heading;
        let cost = container.cost;
        let items = container.options;
        let id = null;

        switch (container.id) {
            case 'color-ac':
                id = 'face-color'
                break;

            case 'color-3mt':
                id = 'face-color'
                break;

            case 'color-3mb':
                id = 'face-color'

                break;
            case 'color-3md':
                id = 'face-color'

                break;
            case 'color-3mm':
                id = 'face-color'

                break;

            default:
                id = container.id
                break;
        }

        if (heading) {
            addHeading(heading, cost);
        }

        if (items) {

            leftSliderContainer.appendChild(addChooseItems(id, items, cost));
        }

    })
    document.getElementById('leftSidebarSlider').style.left = '100%';
    document.getElementById('leftSidebarSlider').style.opacity = '1';
    document.getElementById('leftSidebarSlider').style.width = (220 * column) + 'px';

}
let hideLeftSlider = () => {
    let sliderWidth = parseInt((document.getElementById('leftSidebarSlider').style.width).replace('px', ''))
    let column = sliderWidth / 220
    document.getElementById('leftSidebarSlider').style.left = (column * -220) + 'px';
    document.getElementById('leftSidebarSlider').style.opacity = '0';
    document.querySelector('.left-slider-container').classList.remove('direction-right');

}

let updateActiveItem = (type, value) => {

    let isReturnSize = false;
    let isTrimcapSize = false;

    switch (type) {
        case 'return-size':
            let itemSelectorrs = document.querySelector('.select-return');
            return itemSelectorrs.dataset.activeSecond = value;
            break;
        case 'trimcap-size':
            let itemSelectorts = document.querySelector('.select-trimcap');
            return itemSelectorts.dataset.activeSecond = value;
            break;
        default:

            let itemSelector = document.querySelector('.select-' + type);
            itemSelector.dataset.active = value;
            return itemSelector.querySelector('.name').innerText = value.split('/')[0]
            break;
    }


}

let updateDimenstionDisplay = () => {
    let elementIndexNumber = '#'+getNodeIndex(selectedNode._id);
    let elementDimenstionText = getTextDimensions(selectedNode);

    elementIndexContainer.innerHTML = elementIndexNumber
    elementDimenstionContainer.innerHTML = elementDimenstionText
}

let updateLeftsideBar = () => {
    document.getElementById('cornerRadiusContainer').style.setProperty('display', 'none', 'important');

    if (selectedNode == null) {
        updatePreview();

        document.querySelector('#leftSidebar .item-font').style.setProperty('display', 'none', 'important');
        document.querySelector('#leftSidebar .item-face').style.setProperty('display', 'none', 'important');
        document.querySelector('#leftSidebar .item-return').style.setProperty('display', 'none', 'important');
        document.querySelector('#leftSidebar .item-trimcap').style.setProperty('display', 'none', 'important');
        document.querySelector('#leftSidebar .item-textInput').style.setProperty('display', 'none', 'important');
        document.querySelector('#sizeWidthInput').setAttribute('readonly', 'true');
        document.querySelector('#sizeHeightInput').setAttribute('readonly', 'true');

        return;
    }

    document.querySelector('#leftSidebar .item-textInput').style.setProperty('display', 'block', 'important');
    document.querySelector('#leftSidebar .item-font').style.setProperty('display', 'flex', 'important');
    document.querySelector('#leftSidebar .item-face').style.setProperty('display', 'flex', 'important');
    document.querySelector('#leftSidebar .item-return').style.setProperty('display', 'flex', 'important');
    hasTrimcap == 'on' ? document.querySelector('#leftSidebar .item-trimcap').style.setProperty('display', 'flex', 'important') : document.querySelector('#leftSidebar .item-trimcap').style.setProperty('display', 'none', 'important');
    ;

    document.querySelector('#sizeWidthInput').removeAttribute('readonly');
    document.querySelector('#sizeHeightInput').removeAttribute('readonly');

    let currentElement = getElementById(selectedNode._id);
    if (!currentElement) {
        return;
    }
    switch (selectedNode.getClassName()) {
        case 'RegularPolygon':
            document.querySelector('#leftSidebar .item-textInput').style.setProperty('display', 'none', 'important');
            document.querySelector('#leftSidebar .item-font').style.setProperty('display', 'none', 'important');
            // document.getElementById('cornerRadiusContainer').style.setProperty('display', 'block', 'important');

            break;
        case 'Line':
            document.querySelector('#leftSidebar .item-textInput').style.setProperty('display', 'none', 'important');
            document.querySelector('#leftSidebar .item-font').style.setProperty('display', 'none', 'important');
            document.getElementById('cornerRadiusContainer').style.setProperty('display', 'none', 'important');

            break;
        case 'Circle':
            document.querySelector('#leftSidebar .item-textInput').style.setProperty('display', 'none', 'important');
            document.querySelector('#leftSidebar .item-font').style.setProperty('display', 'none', 'important');
            document.querySelector('#leftSidebar .item-radius').style.setProperty('display', 'none', 'important');
            document.getElementById('cornerRadiusContainer').style.setProperty('display', 'none', 'important');


            break;
        case 'Rect':
            if (selectedNode.getAttr('textIndex')) {
                document.querySelector('#leftSidebar .item-textInput').style.setProperty('display', 'none', 'important');
                document.querySelector('#leftSidebar .item-font').style.setProperty('display', 'none', 'important');
                document.querySelector('#leftSidebar .item-face').style.setProperty('display', 'none', 'important');
                document.querySelector('#leftSidebar .item-return').style.setProperty('display', 'none', 'important');
                document.querySelector('#leftSidebar .item-trimcap').style.setProperty('display', 'none', 'important');
                document.getElementById('cornerRadiusContainer').style.setProperty('display', 'none', 'important');

                document.querySelector('#sizeHeightInput').setAttribute('readonly', true);
                document.querySelector('#sizeHeightInput').value = 8


            } else {
                document.querySelector('#leftSidebar .item-textInput').style.setProperty('display', 'none', 'important');
                document.querySelector('#leftSidebar .item-font').style.setProperty('display', 'none', 'important');
                document.getElementById('cornerRadiusContainer').style.setProperty('display', 'block', 'important');

            }
            break;
        case 'Star':
            document.querySelector('#leftSidebar .item-textInput').style.setProperty('display', 'none', 'important');
            document.querySelector('#leftSidebar .item-font').style.setProperty('display', 'none', 'important');
            //document.getElementById('ornerRadiusContainer').style.setProperty('display', 'block', 'important');

            break;
        case 'Text':
            document.querySelector('#leftSidebar .item-textInput').style.setProperty('display', 'block', 'important');
            document.querySelector('#leftSidebar .item-font').style.setProperty('display', 'flex', 'important');
            updateActiveItem('font', currentElement.font.title + '/' + currentElement.font.code)
            document.getElementById('cornerRadiusContainer').style.setProperty('display', 'none', 'important');

            break;
        default:
            document.querySelector('#leftSidebar .item-textInput').style.setProperty('display', 'none', 'important');
            document.querySelector('#leftSidebar .item-font').style.setProperty('display', 'none', 'important');
            document.querySelector('#leftSidebar .item-face').style.setProperty('display', 'none', 'important');
            document.querySelector('#leftSidebar .item-return').style.setProperty('display', 'none', 'important');
            document.querySelector('#leftSidebar .item-trimcap').style.setProperty('display', 'none', 'important');
            document.getElementById('cornerRadiusContainer').style.setProperty('display', 'none', 'important');
            break;
    }

    if (!selectedNode.getAttr('textIndex')) {
        if (currentElement.faceColor) {
            updateActiveItem('face', currentElement.faceColor.title + '/' + currentElement.faceColor.code)

        }
        if (currentElement.returnColor) {
            updateActiveItem('return', currentElement.returnColor.title + '/' + currentElement.returnColor.code)

        }
        if (currentElement.returnSize) {

            updateActiveItem('return-size', currentElement.returnSize.title + '/' + currentElement.returnSize.code)
        }

        if (currentElement.trimcapColor) {

            updateActiveItem('trimcap', currentElement.trimcapColor.title + '/' + currentElement.trimcapColor.code)
        }
        if (currentElement.trimcapSize) {

            updateActiveItem('trimcap-size', currentElement.trimcapSize.title + '/' + currentElement.trimcapSize.code)
        }

    }

    updateDimenstionDisplay()



}


let updateDetailTable = (elements = elementsArray, extras = extrasArray) => {

    detailTableBody.innerHTML = '';
    for (let i = 0; i < elements.length; i++) {
        let detailTableTR = document.createElement('tr')
        let detailTableId = document.createElement('td')
        detailTableId.innerText = getNodeIndex(elements[i].id);
        detailTableTR.appendChild(detailTableId)

        let detailTableType = document.createElement('td');
        let isChannelLetter = elements[i].text != undefined && elements[i].text.length > 0 ? 'Channel Letter' : 'Raceway';
        if (elements[i].text == undefined) {
            let currentNodeType = getNodeById(elements[i].id).getClassName();
            if (currentNodeType == 'Rect') {
                currentNodeType = 'Rectangle';
            }
            if (currentNodeType == 'RegularPolygon') {
                currentNodeType = 'Triangle';
            }
            if (currentNodeType == 'Line') {
                currentNodeType = 'Arrow';
            }
            if (currentNodeType == 'Star') {
                currentNodeType = 'Starburst';
            }
            detailTableType.innerText = currentNodeType;
            detailTableTR.appendChild(detailTableType)
        } else {

            detailTableType.innerText = isChannelLetter;
            detailTableTR.appendChild(detailTableType)
        }


        let detailTableDimenstion = document.createElement('td')
        detailTableDimenstion.innerText = `${parseFloat(elements[i].height).toFixed(1)} x ${parseFloat(elements[i].width).toFixed(1)}`;
        detailTableTR.appendChild(detailTableDimenstion)

        let detailTableCost = document.createElement('td')
        detailTableCost.innerText = '$' + `${parseFloat(elements[i].cost).toFixed(1)}`;

        detailTableTR.appendChild(detailTableCost)
        let detailTableColorCost = document.createElement('td')
        detailTableColorCost.innerText = '$' + parseFloat((elements[i].colorCost)).toFixed(1);
        detailTableTR.appendChild(detailTableColorCost)

        let detailTableTotalCost = document.createElement('td')
        detailTableTotalCost.innerText = `$${parseFloat(elements[i].cost + elements[i].colorCost).toFixed(2)}`;
        detailTableTR.appendChild(detailTableTotalCost)

        detailTableBody.appendChild(detailTableTR);

    }
    if (extras.powerSupply.qty > 0) {

        let detailTableTR = document.createElement('tr')

        let detailTablePsTitle = document.createElement('td');
        //detailTablePsTitle.classList.add('fw-bold')
        detailTablePsTitle.innerHTML = 'Power Supply: <span class="fw-bold" >' + (extras.powerSupply.value) + '</span>';
        let detailTablePsCost = document.createElement('td')
        detailTablePsTitle.setAttribute('colspan', 5)

        detailTablePsCost.innerText = `$${parseFloat(extras.powerSupply.cost).toFixed(2)}`;
        detailTableTR.appendChild(detailTablePsTitle)
        detailTableTR.appendChild(detailTablePsCost)
        detailTableBody.appendChild(detailTableTR)
    }
    if (extras.lit && extras.lit.qty > 0) {
        if (isLitOption) {
            let detailTableTR = document.createElement('tr')
            let totalElementCost = parseFloat(detailTableBody.dataset.totalElementCost).toFixed(2)
            let litCostPercent = parseFloat(extras.lit.cost)
            let litCost = (totalElementCost * parseFloat(extras.lit.cost)) / 100
            let detailTableLitTitle = document.createElement('td');
            detailTableLitTitle.innerHTML = 'Lit: <span class="fw-bold" >' + (extras.lit.value) + '</span>';
            let detailTableLitCost = document.createElement('td')
            detailTableLitTitle.setAttribute('colspan', 5)

            let litCostText = litCost != 0 ? `$${litCost.toFixed(2)} (${litCostPercent}%)` : `$0.00`;

            detailTableLitCost.innerText = litCostText
            detailTableTR.appendChild(detailTableLitTitle)
            detailTableTR.appendChild(detailTableLitCost)
            detailTableBody.appendChild(detailTableTR)
        }


    }
    if (extras.cable.qty > 0) {

        let detailTableTR = document.createElement('tr')

        let detailTableCableTitle = document.createElement('td');
        //detailTableCableTitle.classList.add('fw-bold')
        detailTableCableTitle.innerHTML = 'Cable: <span class="fw-bold" >' + (extras.cable.value) + '</span>';
        let detailTableCableCost = document.createElement('td')
        detailTableCableTitle.setAttribute('colspan', 5)

        detailTableCableCost.innerText = `$${parseFloat(extras.cable.cost).toFixed(2)}`;
        detailTableTR.appendChild(detailTableCableTitle)
        detailTableTR.appendChild(detailTableCableCost)
        detailTableBody.appendChild(detailTableTR)
    }

    let detailTableTR = document.createElement('tr')

    let detailTablePriceTitle = document.createElement('td');
    detailTablePriceTitle.classList.add('fw-bold')
    detailTablePriceTitle.innerHTML = 'Total : <span class="text-primary"> ' + 0 + ' </span> Objects';

    let detailTableTotalPrice = document.createElement('td')
    detailTablePriceTitle.setAttribute('colspan', 3)
    detailTablePriceTitle.id = 'dtTotalObjDisplay';
    detailTableTotalPrice.setAttribute('colspan', 3)
    detailTableTotalPrice.id = 'dtTotalPriceDisplay';
    detailTableTotalPrice.innerHTML = 'Total Price: <span class="text-success fw-bold">$' + 0 + '</span>';
    detailTableTR.appendChild(detailTablePriceTitle)
    detailTableTR.appendChild(detailTableTotalPrice)
    detailTableBody.appendChild(detailTableTR)


}

function dataURLtoBlob(dataurl) {
    var arr = dataurl.split(','), mime = arr[0].match(/:(.*?);/)[1],
        bstr = atob(arr[1]), n = bstr.length, u8arr = new Uint8Array(n);
    while (n--) {
        u8arr[n] = bstr.charCodeAt(n);
    }
    return new Blob([u8arr], { type: mime });
}

// end utils 


// start main 


const stage = new Konva.Stage({
    container: 'container',
    width: canvasWidth,
    height: canvasHeight,
    fill: '#F5F5F5'
});

if (stage.height() > canvasHeight) {
    stage.height(canvasHeight)
    stage.draw()
}




const previewStage = new Konva.Stage({
    container: previewContainer,
    width: previewCanvasWidth,
    height: previewCanvasWidth,
    background: '#F5F5F5'
})

var previewBackground = new Konva.Rect({
    x: 0,
    y: 0,
    width: previewStage.width(),
    height: previewStage.height(),
    fill: '#F0F0F0', // background color
});

const previewLayer = new Konva.Layer();
previewStage.add(previewLayer)
previewLayer.add(previewBackground);
let previewNoItemText = () => {
    let noItemText = new Konva.Text({
        text: 'No item selected',
        fontSize: 22,
        fill: 'gray'
    })

    noItemText.x((previewStage.width() / 2) - (noItemText.width() / 2))
    noItemText.y((previewStage.height() / 2) - (noItemText.height()))

    previewLayer.add(noItemText)
    previewNodeLists.push(noItemText);
    previewLayer.batchDraw();
}

var background = new Konva.Rect({
    x: 0,
    y: 0,
    width: stage.width(),
    height: stage.height(),
    fill: '#fff', // background color
});

const layer = new Konva.Layer();
stage.add(layer);


layer.add(background)

window.addEventListener('load', function (e) {



    // document.getElementById('addPatternButton').addEventListener('click', function () {
    //     // Load the image
    //     var imageObj = new Image();
    //     imageObj.onload = function () {
    //       // Apply the fillPatternImage to the text
    //       selectedNode.fillPatternImage(imageObj);
    //       selectedNode.fillPatternRepeat('repeat'); // Optional: Repeat pattern inside the text
    //       selectedNode.fillPatternScale({ x: 0.2, y: 0.2 }); // Optional: Scale down the pattern
    //       selectedNode.fillPatternOffset({ x: 0, y: 0 }); // Optional: Offset the pattern inside the text
    //       selectedNode.fillPatternRotation(0); // Optional: Rotate the pattern inside the text

    //       console.log(selectedNode)
    //       // Redraw the layer to reflect changes
    //       layer.batchDraw();
    //     };

    //     // Set the source of the image
    //     imageObj.src = 'http://localhost/wholesale/wp-content/themes/wholesale/img/dual-color-black.jpeg'; // Replace with your image URL
    //   });
    function addText(text, x = canvasWidth / 2, y = canvasHeight / 2, isUpdateId = false) {
        dualColorBg = 'http://localhost/wholesale/wp-content/themes/wholesale/img/dual-color-black.jpeg'; //
        var imageObj = new Image();
        imageObj.src = dualColorBg;
        //Replace with your image URL
        imageObj;

        let textConfig = {
            x: x,
            y: y,
            text: text,
            fontSize: fontSize,
            fontFamily: activeFontCode,
            fill: activeFaceCode,
            stroke: activeTrimcapColorCode,
            strokeWidth: activeTrimcapSizeCode,
            shadowColor: activeReturnColorCode,
            shadowOffsetX: activeReturnSizeCode,
            shadowOffsetY: activeReturnSizeCode,
            shadowBlur: activeReturnSizeCode,
            draggable: true,
            rotateLineVisible: false,
        }
        if (hasDualColor) {
            textConfig = {
                x: x,
                y: y,
                text: text,
                fontSize: fontSize,
                fontFamily: fontFamily,
                stroke: trimCapColor,
                strokeWidth: trimCapSize,
                shadowColor: returnColor,
                shadowOffsetX: returnSize,
                shadowOffsetY: returnSize,
                fillPatternImage: imageObj, // Set the pattern image
                fillPatternRepeat: 'repeat', // Repeat the pattern inside the text
                fillPatternOffset: { x: 0, y: 0 }, // Offset the pattern (optional)
                fillPatternScale: { x: 0.2, y: 0.2 }, // Scale the pattern (optional)
                fillPatternRotation: 0, // Rotate the pattern (optional)
                shadowBlur: returnSize,
                draggable: true,
                rotateLineVisible: false,
            }
        }


        const textNode = new Konva.Text(textConfig);



        let changeEvent = new CustomEvent('change', { bubbles: true })
        textNode.x(x - ((textNode.width() * textNode.scaleX()) / 2))
        textNode.y(y - ((textNode.height() * textNode.scaleY()) / 2))




        layer.add(textNode);



        const tr = new Konva.Transformer({
            nodes: [textNode],
            keepRatio: true,
            boundBoxFunc: function (oldBoundBox, newBoundBox) {

                let stageScaleX = stage.scaleX();
                let stageScaleY = stage.scaleY();
                // Constrain height
                if (pxToIn(newBoundBox.height / stageScaleY) > maxHeight) {

                    oldBoundBox.height = maxHeight * dpi
                    sizeHeightInput.value = maxHeight;
                    sizeHeightInput.dispatchEvent(changeEvent)
                    return oldBoundBox

                }
                if (pxToIn(newBoundBox.height / stageScaleY) < minHeight) {
                    oldBoundBox.height = minHeight * dpi
                    sizeHeightInput.value = minHeight;
                    sizeHeightInput.dispatchEvent(changeEvent)
                    return oldBoundBox
                }

                return newBoundBox;
            },
            rotateEnabled: false,
            rotateLineVisible: false,
            borderStroke: 'gray',        // Set border color to gray
            borderDash: [4, 4],          // Set dashed border (4 pixels dash, 4 pixels gap)
            borderStrokeWidth: 2,        // Set border width
            anchorStroke: 'gray',
            //enabledAnchors: ['top-left', 'top-right', 'bottom-left', 'bottom-right']
        });

        tr.nodes([textNode])

        layer.add(tr);

        textNode.setAttr('transformer', tr);

        textNode.on('transform', function () {
            selectedNode = textNode;
            selectedNodeType = textNode.getClassName();

            textInput.value = textNode.text();
            updatePreview('text', textNode);


            updateHeightWidthInput(textNode.height() * textNode.scaleY(), textNode.width() * textNode.scaleX(), 'text')
            isTextTransform = true;
            updateLeftsideBar()

            let widthInInch = pxToIn(textNode.width() * textNode.scaleX());
            let heightInInch = pxToIn(textNode.height() * textNode.scaleY());
            let textLength = ((textNode.text()).replace(' ', '')).length

            let totalLength = ((textNode.text()).replace(' ', '')).length;
            let singleHeightInch = (textNode.height() * textNode.scaleY()) / dpi;
            let totalHeightInch = singleHeightInch * totalLength
            totalColorCost = parseFloat(totalHeightInch * parseFloat(colorCost));
            store.dispatch({
                type: 'UPDATE_ELEMENT', payload: {
                    'id': textNode._id,
                    'width': widthInInch,
                    'height': heightInInch,
                    'text': textNode.text(),
                    'cost': costPerInch(heightInInch) * textLength,
                    'fontSize': textNode.fontSize() * textNode.scaleX(),
                    colorCost: totalColorCost,
                    x: textNode.x(),
                    y: textNode.y(),
                    scale: {
                        x: textNode.scaleX(),
                        y: textNode.scaleY()
                    }
                }
            });

            updateLeftsideBar();
            updateHeightWidthDisplay();
            fontSize = textNode.fontSize() * textNode.scaleX();


        });

        textNode.on('dragmove', function () {
            selectedNode = textNode;
            selectedNodeType = textNode.getClassName();
            textInput.value = textNode.text();
            updateHeightWidthDisplay()
            updatePreview('text', textNode);

            updateHeightWidthInput(textNode.height() * textNode.scaleY(), textNode.width() * textNode.scaleX(), 'text')
            updateLeftsideBar()

            let widthInInch = pxToIn(textNode.width() * textNode.scaleX());
            let heightInInch = pxToIn(textNode.height() * textNode.scaleY());
            let textLength = ((textNode.text()).replace(' ', '')).length;
            totalColorCost = (heightInInch * textLength) * parseFloat(colorCost);
            store.dispatch({
                type: 'UPDATE_ELEMENT', payload: {
                    'id': textNode._id,
                    'width': widthInInch,
                    'height': heightInInch,
                    'text': textNode.text(),
                    'cost': costPerInch(heightInInch) * textLength,
                    colorCost: totalColorCost,
                    x: textNode.x(),
                    y: textNode.y(),
                }
            });

        });


        textNode.on('click', e => {
            selectedNode = textNode
            selectedNodeType = textNode.getClassName();
            textInput.value = textNode.text();
            updatePreview('text', textNode);
            updateLeftsideBar();
            let heightInInch = pxToIn(textNode.height() * textNode.scaleY());
            let widthInInch = pxToIn(textNode.width() * textNode.scaleX());
            updateHeightWidthInput(textNode.height() * textNode.scaleY(), textNode.width() * textNode.scaleX(), type = 'text');
        })

        nodeLists.push({ type: 'text', node: textNode, id: currentElementIndex });

        selectedNode = textNode
        selectedNodeType = textNode.getClassName();

        layer.batchDraw();
        updateHeightWidthDisplay();
        updatePreview('text', textNode);
        updateLeftsideBar()

        let widthInInch = pxToIn(textNode.width() * textNode.scaleX());
        let heightInInch = pxToIn(textNode.height() * textNode.scaleY());
        let textLength = ((textNode.text()).replace(' ', '')).length

        let totalLength = ((textNode.text()).replace(' ', '')).length;
        let singleHeightInch = parseFloat(textNode.height() * textNode.scaleY()) / dpi;
        let totalHeightInch = singleHeightInch * totalLength
        totalColorCost = totalHeightInch * parseFloat(colorCost);


        store.dispatch({
            type: 'ADD_ELEMENT', payload: {
                'id': textNode._id,
                'width': widthInInch,
                'height': heightInInch,
                'text': textNode.text(),
                'cost': costPerInch(heightInInch) * textLength,
                'colorCost': totalColorCost,
                faceColor: {
                    title: activeFaceTitle,
                    code: activeFaceCode,
                },
                returnColor: {
                    title: activeReturnColorTitle,
                    code: activeReturnColorCode
                },
                trimcapColor: {
                    title: activeTrimcapColorTitle,
                    code: activeTrimcapColorCode
                },
                returnSize: {
                    title: activeReturnSizeTitle,
                    code: activeReturnSizeCode
                },
                trimcapSize: {
                    title: activeTrimcapSizeTitle,
                    code: activeTrimcapSizeCode
                },
                faceCostPerInch: parseFloat(colorCost),
                colorCost: totalColorCost,
                scale: {
                    x: textNode.scaleX(),
                    y: textNode.scaleY()
                },
                x: textNode.x(),
                y: textNode.y(),
            }
        });



        currentElementIndex = currentElementIndex + 1;
        updateBottombarOverlay()
        updateLeftsideBar()
        setTextFSI(textNode._id, 'yes')



        return textNode._id;


    }



    function addShape(shapeType) {
        let shape;
        let previewnType = null;
        let isKeepRatio = true;
        let changeEvent = new CustomEvent('change', { bubbles: true });
        const shapeConfig = {
            x: 150,
            y: 150,
            draggable: true,
            fill: activeFaceCode,
            stroke: activeTrimcapColorCode,
            strokeWidth: activeTrimcapSizeCode,
            shadowColor: activeReturnColorCode,
            shadowOffsetX: activeReturnSizeCode,
            shadowOffsetY: activeReturnSizeCode,
            shadowBlur: activeReturnSizeCode,

        };

        switch (shapeType) {
            case 'rectangle':
                shape = new Konva.Rect({
                    ...shapeConfig,
                    width: 200,
                    height: 100,
                    cornerRadius: 8
                });
                previewnType = 'rect';
                break;

            case 'arrow':

                var points = [
                    37.5, 67,  // Point A
                    112.5, 67, // Point B
                    112.5, 33.5, // Point C
                    187.5, 83.75, // Point D (tip of the arrow)
                    112.5, 134, // Point E
                    112.5, 100.5, // Point F
                    37.5, 100.5  // Point G
                ];

                // Find the bounding box of the points
                var minX = Math.min(...points.filter((_, i) => i % 2 === 0));
                var maxX = Math.max(...points.filter((_, i) => i % 2 === 0));
                var minY = Math.min(...points.filter((_, i) => i % 2 === 1));
                var maxY = Math.max(...points.filter((_, i) => i % 2 === 1));

                // Calculate the center of the arrow
                var arrowCenterX = (minX + maxX) / 2;
                var arrowCenterY = (minY + maxY) / 2;

                // Calculate the center of the stage
                var stageCenterX = 150;
                var stageCenterY = 150;

                // Calculate the offset to move the arrow to the center of the stage
                var offsetX = stageCenterX - arrowCenterX;
                var offsetY = stageCenterY - arrowCenterY;

                // Apply the offset to all points
                var centeredPoints = points.map((value, index) => {
                    if (index % 2 === 0) {
                        return value + offsetX;  // Adjust x-coordinates
                    } else {
                        return value + offsetY;  // Adjust y-coordinates
                    }
                });



                // Create the arrow shape using Konva.Line
                shape = new Konva.Line({
                    points: centeredPoints,
                    fill: shapeConfig.fill,
                    stroke: shapeConfig.stroke,
                    strokeWidth: 2,
                    closed: true,
                    shadowColor: shapeConfig.shadowColor,
                    shadowBlur: shapeConfig.shadowBlur,
                    shadowOffset: { x: shapeConfig.shadowOffsetX, y: shapeConfig.shadowOffsetY },
                    draggable: true,
                });


                updateHeightWidthInput(101, 150)
                previewnType = 'arrow';

                break;
            case 'circle':
                shape = new Konva.Circle({
                    ...shapeConfig,
                    radius: 50,
                });

                previewnType = 'circle';


                break;
            case 'triangle':
                shape = new Konva.RegularPolygon({
                    ...shapeConfig,
                    sides: 3,
                    radius: 50 * triangleReduction
                });
                previewnType = 'triangle';
                isKeepRatio = false;
                break;
            case 'star':
                shape = new Konva.Star({
                    ...shapeConfig,
                    numPoints: 5,
                    innerRadius: 30,
                    outerRadius: 50,
                });
                updatePreview('star', shape);
                previewnType = 'star';

                break;
        }
        selectedNodeType = shape.getClassName();

        updatePreview(previewnType, shape);



        layer.add(shape);


        if (shape.getClassName() == "Rect") {
            isKeepRatio = false;
        }

        const tr = new Konva.Transformer({
            nodes: [shape],
            keepRatio: isKeepRatio,
            boundBoxFunc: function (oldBoundBox, newBoundBox) {
                // Set minimum and maximum size constraints

                let stageScaleX = stage.scaleX();
                let stageScaleY = stage.scaleY();
                let reduction = shape.getClassName() == 'RegularPolygon' ? triangleReduction : 1;
                // Constrain height


                if (pxToIn(newBoundBox.height / stageScaleY) * reduction > maxHeight) {

                    oldBoundBox.height = maxHeight * dpi
                    sizeHeightInput.value = maxHeight;
                    sizeHeightInput.dispatchEvent(changeEvent)
                    return oldBoundBox

                }
                if (pxToIn(newBoundBox.height / stageScaleY) * reduction < minHeight) {
                    oldBoundBox.height = minHeight * dpi
                    sizeHeightInput.value = minHeight;
                    sizeHeightInput.dispatchEvent(changeEvent)
                    return oldBoundBox
                }

                if (pxToIn(newBoundBox.width / stageScaleX) * reduction > maxWidth) {

                    oldBoundBox.width = maxWidth * dpi
                    sizeWidthInput.value = maxWidth;
                    sizeWidthInput.dispatchEvent(changeEvent)
                    return oldBoundBox

                }
                if (pxToIn(newBoundBox.width / stageScaleX) * reduction < minWidth) {
                    oldBoundBox.width = minWidth * dpi
                    sizeWidthInput.value = minWidth;
                    sizeWidthInput.dispatchEvent(changeEvent)
                    return oldBoundBox
                }
                return newBoundBox;
            },
            rotateEnabled: false,
            borderStroke: 'gray',        // Set border color to gray
            borderDash: [4, 4],          // Set dashed border (4 pixels dash, 4 pixels gap)
            borderStrokeWidth: 2,        // Set border width
            anchorStroke: 'gray',
            //enabledAnchors: ['top-left', 'top-right', 'bottom-left', 'bottom-right']
        });
        layer.add(tr);
        shape.setAttr('transformer', tr);
        selectedNode = shape;

        shape.on('dragmove', function () {
            selectedNode = shape;
            selectedNodeType = shape.getClassName();
            updatePreview(previewnType, shape);
            updateHeightWidthDisplay();
            updateLeftsideBar()
            faceColor = shape.attrs.fill
            returnColor = shape.attrs.shadowColor
            trimcap = shape.attrs.stroke


            store.dispatch({
                type: 'UPDATE_ELEMENT', payload: {
                    'id': shape._id,
                    x: shape.x(),
                    y: shape.y(),
                }
            });

        });

        shape.on('transform', e => {
            selectedNode = shape;
            selectedNodeType = shape.getClassName();
            updateHeightWidthDisplay()
            updatePreview(previewnType, shape);
            updateLeftsideBar()

            let currentElementColorCost = getElementById(shape._id) ? getElementById(shape._id).faceCostPerInch : 0;
            switch (shape.getClassName()) {
                case 'RegularPolygon':
                    let triHeightInch = pxToIn(shape.height() * shape.scaleY() / triangleReduction);
                    let triWidthInch = pxToIn(shape.width() * shape.scaleX() / triangleReduction);
                    let totalTriColorCost = parseFloat(currentElementColorCost) * triWidthInch

                    let totalTriCost = costPerInch(triWidthInch)
                    if (triHeightInch > triWidthInch) {
                        totalTriCost = costPerInch(triHeightInch)
                        totalTriColorCost = parseFloat(currentElementColorCost) * triHeightInch
                    }
                    store.dispatch({
                        type: 'UPDATE_ELEMENT', payload: {
                            'id': shape._id,
                            'cost': totalTriCost,
                            'width': triWidthInch,
                            'height': triHeightInch,
                            colorCost: totalTriColorCost,
                            faceCostPerInch: colorCost,
                            x: shape.x(),
                            y: shape.y(),
                            scale: {
                                x: shape.scaleX(),
                                y: shape.scaleY()
                            },
                        }
                    });

                    updateHeightWidthInput(shape.height() * shape.scaleY() / triangleReduction, shape.width() * shape.scaleX() / triangleReduction, previewnType)

                    break;
                case 'line':
                    let points = shape.points() || [];
                    let arrowHeightInch = pxToIn(shape.height() * shape.scaleY());
                    let arrowWidthInch = pxToIn(shape.width() * shape.scaleX());

                    let totalArrowCost = (costPerInch(arrowWidthInch))
                    let totalArrowColorCost = parseFloat(currentElementColorCost) * arrowWidthInch
                    if (arrowHeightInch > arrowWidthInch) {
                        totalArrowCost = (costPerInch(arrowHeightInch))
                        totalArrowColorCost = parseFloat(currentElementColorCost) * arrowHeightInch
                    }

                    store.dispatch({
                        type: 'UPDATE_ELEMENT', payload: {
                            'id': shape._id,
                            'cost': totalArrowCost,
                            'width': arrowWidthInch,
                            'height': arrowHeightInch,
                            colorCost: totalArrowColorCost,
                            faceCostPerInch: colorCost,
                            x: shape.x(),
                            y: shape.y(),
                            scale: {
                                x: shape.scaleX(),
                                y: shape.scaleY()
                            },
                        }
                    });

                    updateHeightWidthInput(arrowHeightInch, arrowWidthInch)
                    break;


                default:
                    updateHeightWidthInput(shape.height() * shape.scaleY(), shape.width() * shape.scaleX(), previewnType)
                    let shapeHeightInch = pxToIn(shape.height() * shape.scaleY());
                    let shapeWidthInch = pxToIn(shape.width() * shape.scaleX());
                    let totalShapeCost = (costPerInch(shapeWidthInch));
                    let totalShapeColorCost = parseFloat(currentElementColorCost) * shapeWidthInch
                    if (shapeWidthInch < shapeHeightInch) {
                        totalShapeCost = (costPerInch(shapeHeightInch));
                        totalShapeColorCost = parseFloat(currentElementColorCost) * shapeHeightInch
                    }
                    store.dispatch({
                        type: 'UPDATE_ELEMENT', payload: {
                            'id': shape._id,
                            'cost': totalShapeCost,
                            'width': shapeWidthInch,
                            'height': shapeHeightInch,
                            colorCost: totalShapeColorCost,
                            faceCostPerInch: colorCost,
                            x: shape.x(),
                            y: shape.y(),
                            scale: {
                                x: shape.scaleX(),
                                y: shape.scaleY()
                            },
                        }
                    });


                    break;
            }


            faceColor = shape.attrs.fill
            returnColor = shape.attrs.shadowColor
            trimcap = shape.attrs.stroke
        })


        shape.on('transformend', e => {

            if (selectedNode.getClassName() == 'Line') {
                let shapeHeightInch = pxToIn(shape.height() * shape.scaleY());
                let shapeWidthInch = pxToIn(shape.width() * shape.scaleX());

                shape.points(updateArrowLine(shapeHeightInch * dpi, null, 'height'));
                shape.scaleY(1)

                shape.points(updateArrowLine(null, shapeWidthInch * dpi, 'width'));
                shape.scaleX(1);


                triggerTransformEvent()


            }
        })

        shape.on('click', e => {
            selectedNode = shape;
            updatePreview(previewnType, shape);
            updateLeftsideBar()

            updateHeightWidthInput(shape.height() * shape.scaleY(), shape.width() * shape.scaleX(), previewnType)
            if (shape.getClassName() != 'RegularPolygon') {
                //updateHeightWidthInput(shape.height() * shape.scaleY(), shape.width() * shape.scaleX(), previewnType)
                updateHeightWidthInput(shape.height() * shape.scaleY(), shape.width() * shape.scaleX(), previewnType)

            } else {


                updateHeightWidthInput(shape.height() * shape.scaleY() / triangleReduction, shape.width() * shape.scaleX() / triangleReduction, previewnType)
            }
            selectedNodeType = shape.getClassName();
            faceColor = shape.attrs.fill
            returnColor = shape.attrs.shadowColor
            trimcap = shape.attrs.stroke
        })

        nodeLists.push({ type: 'shape', node: shape, id: currentElementIndex });


        layer.batchDraw();
        updateHeightWidthDisplay()


        switch (shape.getClassName()) {
            case 'RegularPolygon':
                let triHeightInch = pxToIn(shape.height() * shape.scaleY() / triangleReduction);
                let triWidthInch = pxToIn(shape.width() * shape.scaleX() / triangleReduction);
                let totalTriCost = costPerInch(triWidthInch)

                let totalColorCost = triWidthInch * parseFloat(colorCost);

                if (triWidthInch < triHeightInch) {
                    totalTriCost = costPerInch(triHeightInch)
                    totalColorCost = triHeightInch * parseFloat(colorCost);
                }
                shape.scaleX(2.8)

                updateHeightWidthInput(triWidthInch * dpi, triWidthInch * dpi)



                store.dispatch({
                    type: 'ADD_ELEMENT', payload: {
                        'id': shape._id,
                        'type': shape.getClassName(),
                        'cost': parseFloat(totalTriCost),
                        'width': triWidthInch,
                        'height': triHeightInch,
                        faceColor: {
                            title: activeFaceTitle,
                            code: activeFaceCode,
                        },
                        returnColor: {
                            title: activeReturnColorTitle,
                            code: activeReturnColorCode
                        },
                        trimcapColor: {
                            title: activeTrimcapColorTitle,
                            code: activeTrimcapColorCode
                        },
                        returnSize: {
                            title: activeReturnSizeTitle,
                            code: activeReturnSizeCode
                        },
                        trimcapSize: {
                            title: activeTrimcapSizeTitle,
                            code: activeTrimcapSizeCode
                        },
                        faceCostPerInch: parseFloat(colorCost),
                        colorCost: totalColorCost,

                    }
                });


                break;

            case 'Line':
                let points = shape.points() || [];

                let arrowHeightInch = (shape.points()[9] - shape.points()[5]) / dpi
                let arrowWidthInch = (shape.points()[6] - shape.points()[0]) / dpi
                let totalArrowCost = costPerInch(arrowWidthInch)
                let totalArrowColorCost = arrowWidthInch * parseFloat(colorCost)

                if (arrowWidthInch < arrowHeightInch) {
                    totalArrowCost = costPerInch(arrowHeightInch)
                    totalArrowColorCost = arrowHeightInch * parseFloat(colorCost)
                }
                store.dispatch({
                    type: 'ADD_ELEMENT', payload: {
                        'id': shape._id,
                        cost: totalArrowCost,
                        'width': arrowWidthInch,
                        'height': arrowHeightInch,
                        colorCost: totalArrowColorCost,
                        faceCostPerInch: colorCost,
                        x: shape.x(),
                        y: shape.y(),
                        faceColor: {
                            title: activeFaceTitle,
                            code: activeFaceCode,
                        },
                        returnColor: {
                            title: activeReturnColorTitle,
                            code: activeReturnColorCode
                        },
                        trimcapColor: {
                            title: activeTrimcapColorTitle,
                            code: activeTrimcapColorCode
                        },
                        returnSize: {
                            title: activeReturnSizeTitle,
                            code: activeReturnSizeCode
                        },
                        trimcapSize: {
                            title: activeTrimcapSizeTitle,
                            code: activeTrimcapSizeCode
                        },
                        scale: {
                            x: shape.scaleX(),
                            y: shape.scaleY()
                        },
                        points: points,
                    }
                });

                break;

            default:

                updateHeightWidthInput(shape.height() * shape.scaleY(), shape.width() * shape.scaleX(), previewnType)
                let shapeHeightInch = pxToIn(shape.height() * shape.scaleY())
                let shapeWidthInch = pxToIn(shape.width() * shape.scaleX())

                let radius = shape.getClassName() == 'Rect' ? 8 : undefined;

                let totalShapeCost = costPerInch(shapeWidthInch)
                let totalShapeColorCost = shapeWidthInch * parseFloat(colorCost);


                if (shapeHeightInch > shapeWidthInch) {
                    totalShapeCost = costPerInch(shapeHeightInch)
                    totalShapeColorCost = shapeHeightInch * parseFloat(colorCost);
                }

                store.dispatch({
                    type: 'ADD_ELEMENT', payload: {
                        'id': shape._id,
                        'type': shape.getClassName(),
                        'cost': (totalShapeCost),
                        'width': shapeWidthInch,
                        'height': shapeHeightInch,
                        faceCostPerInch: colorCost,
                        faceColor: {
                            title: activeFaceTitle,
                            code: activeFaceCode,
                        },
                        returnColor: {
                            title: activeReturnColorTitle,
                            code: activeReturnColorCode
                        },
                        trimcapColor: {
                            title: activeTrimcapColorTitle,
                            code: activeTrimcapColorCode
                        },
                        returnSize: {
                            title: activeReturnSizeTitle,
                            code: activeReturnSizeCode
                        },
                        trimcapSize: {
                            title: activeTrimcapSizeTitle,
                            code: activeTrimcapSizeCode
                        },
                        faceCostPerInch: parseFloat(colorCost),
                        colorCost: totalShapeColorCost,
                        radius
                    }
                });
                break;
        }
        layer.batchDraw();

        updateLeftsideBar();
        currentElementIndex = currentElementIndex + 1;
        updateBottombarOverlay()
    }

    let addRaceway = e => {
        // Create the rectangle
        var rectWidth = 600;
        var rectHeight = 80;
        var raceway = new Konva.Rect({
            x: (canvasWidth - rectWidth) / 2,
            y: (canvasHeight - rectHeight) / 2,
            width: rectWidth,
            height: rectHeight,
            fill: '#D3D3D3',
            stroke: 'transparent',
            strokeWidth: 2,
            shadowColor: '#7f7b7b',
            shadowOffsetX: 5,
            shadowOffsetY: 5,
            shadowBlur: 5,
            id: 'raceway-rect',
            draggable: true,
            opacity: 0.5,
            rotateLineVisible: false,
        });

        updatePreview('raceway', raceway)



        const tr = new Konva.Transformer({
            nodes: [raceway],
            rotateEnabled: false,
            rotateLineVisible: false,
            borderStroke: 'gray',        // Set border color to gray
            borderDash: [4, 4],          // Set dashed border (4 pixels dash, 4 pixels gap)
            borderStrokeWidth: 2,        // Set border width
            anchorStroke: 'gray',
            enabledAnchors: ['middle-right', 'middle-left']
        });


        raceway.setAttr('transformer', tr);

        layer.add(tr);
        layer.add(raceway);



        // Create the text
        var racewayText = new Konva.Text({
            text: 'Raceway',
            fontSize: 24,
            fontFamily: 'Arial',
            fill: 'gray',

        });

        raceway.setAttr('textIndex', racewayText);

        layer.add(racewayText);

        raceway.on('dragmove', e => {
            racewayText.x(raceway.x() + ((raceway.width() * raceway.scaleX()) - racewayText.width()) / 2);
            racewayText.y(raceway.y() + (raceway.height() * raceway.scaleY() / 2) - (racewayText.height()) / 2);
            updateHeightWidthDisplay()

            selectedNode = raceway;
            updateHeightWidthInput(selectedNode.height() * selectedNode.scaleY(), selectedNode.width() * selectedNode.scaleX());
            updatePreview('raceway', raceway)
            updateLeftsideBar()

            store.dispatch({
                type: 'UPDATE_ELEMENT', payload: {
                    'id': raceway._id,
                    x: raceway.x(),
                    y: raceway.y(),
                }
            });


        });

        raceway.on('click', e => {
            selectedNode = raceway;
            updatePreview('raceway', raceway)
            updateHeightWidthInput(selectedNode.height() * selectedNode.scaleY(), selectedNode.width() * selectedNode.scaleX());
            updateLeftsideBar()

        })

        raceway.on('transform', e => {
            racewayText.x(((raceway.x() + (raceway.width() * raceway.scaleX()) / 2)) - (racewayText.width() / 2));
            racewayText.y(raceway.y() + (((raceway.height() * raceway.scaleY()) / 2) - (racewayText.height() / 2)));
            updateLeftsideBar()
            updateHeightWidthDisplay()
            selectedNode = raceway;
            updatePreview('raceway', raceway)

            layer.batchDraw();
            updateHeightWidthInput(8, raceway.width() * raceway.scaleX(), 'raceway')

            store.dispatch({
                type: 'UPDATE_ELEMENT', payload: {
                    'id': raceway._id,
                    text,
                    'type': 'Raceway',
                    'cost': ((pxToIn(raceway.width() * raceway.scaleX()) / 12).toFixed(0) * 50).toFixed(2),
                    'width': pxToIn(raceway.width() * raceway.scaleX()),
                    'height': pxToIn(raceway.height() * raceway.scaleY()),
                    x: raceway.x(),
                    y: raceway.y(),
                    scale: {
                        x: raceway.scaleX(),
                        y: raceway.scaleY()
                    },

                }
            });

        })
        // Center the text within the rectangle
        racewayText.x(raceway.x() + (raceway.width() - racewayText.width()) / 2);
        racewayText.y(raceway.y() + (raceway.height() - racewayText.height()) / 2);
        layer.add(racewayText);

        nodeLists.push({ type: 'raceway', node: raceway, id: currentElementIndex });


        selectedNode = raceway;
        updatePreview('raceway', raceway)


        let textIndex = selectedNode.getAttr('textIndex')
        racewayText.moveToBottom()
        raceway.moveToBottom();
        tr.moveToBottom()
        background.moveToBottom();
        layer.draw();
        updateHeightWidthDisplay()

        let text = '';

        selectedNode = raceway;

        updateHeightWidthInput(raceway.height() * raceway.scaleY(), raceway.width() * raceway.scaleX(), 'raceway')
        store.dispatch({
            type: 'ADD_ELEMENT', payload: {
                'id': raceway._id,
                text,
                'type': 'Raceway',
                'cost': ((pxToIn(raceway.width() * raceway.scaleX()) / 12) * 50).toFixed(2),
                'width': pxToIn(raceway.width() * raceway.scaleX()),
                'height': pxToIn(raceway.height() * raceway.scaleY()),
                'colorCost': 0,
                scale: {
                    x: raceway.scaleX(),
                    y: raceway.scaleY()
                },

            }
        });
        updateLeftsideBar();


    }

    let updateEditor = () => {
        if (selectedNode == null) {
            for (let i = 0; i < nodeLists.length; i++) {
                let nodeContainer = nodeLists[i].node;
                let transformer = nodeContainer.getAttr('transformer');

                if (nodeContainer._id != selectedNodeId) {



                    if (transformer) {
                        transformer.hide();
                    }

                }
            }
        };
        let selectedNodeId = selectedNode._id;
        for (let i = 0; i < nodeLists.length; i++) {
            let nodeContainer = nodeLists[i].node;
            let transformer = nodeContainer.getAttr('transformer');
            if (nodeContainer._id != selectedNodeId) {



                if (transformer) {
                    transformer.hide();
                }

            } else {

                if (transformer) {
                    transformer.show();
                }

            }
        }
        triggerTransformEvent();
    }

    let handleDrawEvent = () => {
        updateLeftsideBar();
    }

    stage.on('draw dragmove transfrom', handleDrawEvent);


    drawHeightArrows(20, `0"`);
    drawWidthArrows(20, `0"`);
    previewNoItemText();
    updateLeftsideBar();
    updateBottombarOverlay();


    // end main

    // start script 

    window.addEventListener('resize', function () {
        var width = container.clientWidth;
        var height = container.clientWidth;
        stage.width(width);
        stage.height(height);
    });


    infoButtons.forEach(button => {
        button.addEventListener('click', function () {
            // Access the .info-btn-content within the same container
            const infoContent = this.parentElement.querySelector('.info-btn-content');

            if (infoContent) {
                infoContent.style.display = infoContent.style.display === 'none' ? 'block' : 'none';

            }

            // Do something with the .info-btn-content (e.g., toggle visibility)
        });
    });

    addTextBtn.addEventListener('click', function () {

        const text = textInput.value || 'Channel';
        fontSize = (parseFloat(sizeHeightInput.value) * dpi) || 10 * dpi
        addText(text);
        textInput.value = text;
        saveState();
        triggerTransformEvent();

    });

    addRacewayButton.addEventListener('click', function () {
        addRaceway();
    });

    shapeButtons.forEach((el) => {
        el.addEventListener('click', (e) => {
            let shapeType = e.currentTarget.dataset.shape;
            addShape(shapeType);
            const mouseoutEvent = new MouseEvent('mouseleave', {
                bubbles: true, // Allows the event to bubble up through the DOM
                cancelable: true // Allows the event to be canceled
            });
            document.querySelector('.shape-dropdown').dispatchEvent(mouseoutEvent)
            triggerTransformEvent();

        });
    })


    document.querySelector('.shape-dropdown').addEventListener('mouseenter', e => {
        document.querySelector('.shape-dropdown .shapes-container').style.display = 'block';

    })

    document.querySelector('.shape-dropdown').addEventListener('mouseleave', e => {
        document.querySelector('.shape-dropdown .shapes-container').style.display = 'none';

    })
    duplicateBtn.addEventListener('click', function (e) {

        if (selectedNode == null) return;


        if (selectedNode.getClassName() == 'Text') {
            fontSize = selectedNode.fontSize() * selectedNode.scaleX();
            fontFamily = selectedNode.attrs.fontFamily
            faceColor = selectedNode.attrs.fill
            trimCapColor = selectedNode.attrs.stroke
            trimCapSize = selectedNode.attrs.strokeWidth;
            returnColor = selectedNode.attrs.shadowColor
            returnSize = selectedNode.attrs.shadowOffsetX;

            let selectedElement = getElementById(selectedNode._id)

            activeFaceTitle = selectedElement.faceColor.title;
            activeFaceCode = selectedElement.faceColor.code;

            activeReturnColorTitle = selectedElement.returnColor.title;
            activeReturnColorCode = selectedElement.returnColor.code;

            activeTrimcapColorTitle = selectedElement.trimcapColor.title;
            activeTrimcapColorCode = selectedElement.trimcapColor.code;

            activeReturnSizeTitle = selectedElement.returnSize.title;
            activeReturnSizeCode = selectedElement.returnSize.code;

            activeTrimcapSizeTitle = selectedElement.trimcapSize.title;
            activeTrimcapSizeCode = selectedElement.trimcapSize.code;

            colorCost = parseFloat(selectedElement.faceCostPerInch)
            return addText(selectedNode.text(), selectedNode.x() + 100, selectedNode.y() + 100);

        }
        let selectedNodeId = selectedNode._id;
        let cloneNode = selectedNode.clone({
            x: selectedNode.x() + 100, // Adjust position of the cloned arrow
            y: selectedNode.y() + 100, // Adjust position of the cloned arrow
        });

        let enabledAnchors = undefined;


        let text = cloneNode.getClassName() == 'Text' ? cloneNode.text() : ' ';
        let textLength = (text.replace(' ', '')).length || 1;


        if (cloneNode.getAttr('textIndex')) {
            let selectedRacewayText = cloneNode.getAttr('textIndex');
            var clonedRacewayText = selectedRacewayText.clone(
                {
                    x: selectedRacewayText.x() + 100,
                    y: selectedRacewayText.y() + 100,

                }
            )

            layer.add(clonedRacewayText);
            cloneNode.setAttr('textIndex', clonedRacewayText);
        }


        if (cloneNode.getClassName() == 'Rect') {
            if (cloneNode.getAttr('textIndex')) {
                enabledAnchors = ['middle-right', 'middle-left']
            }
        }



        const tr = new Konva.Transformer({
            nodes: [cloneNode],
            keepRatio: true,
            rotationSnaps: false,
            boundBoxFunc: function (oldBoundBox, newBoundBox) {
                // Set minimum and maximum size constraints
                var minWidth = 82;
                var minHeight = 82;
                var maxWidth = 455;
                var maxHeight = 455;

                if (cloneNode.getClassName() == 'Text') {
                    // Constrain height
                    if (newBoundBox.height < minHeight) {
                        oldBoundBox.height = minHeight;

                        return oldBoundBox;
                    } else if (newBoundBox.height > maxHeight) {
                        oldBoundBox.height = maxHeight;

                        return oldBoundBox;
                    }

                } else {
                    // Constrain width
                    if (newBoundBox.width < minWidth) {
                        oldBoundBox.width = minWidth;
                        return oldBoundBox;
                    } else if (newBoundBox.width > maxWidth) {
                        oldBoundBox.width = maxWidth;
                        return oldBoundBox;
                    }
                }
                return newBoundBox;
            },
            rotateEnabled: false,
            rotateLineVisible: false,
            borderStroke: 'gray',        // Set border color to gray
            borderDash: [4, 4],          // Set dashed border (4 pixels dash, 4 pixels gap)
            borderStrokeWidth: 2,        // Set border width
            anchorStroke: 'gray',
            enabledAnchors
        });




        cloneNode.setAttr('transformer', tr);

        layer.add(cloneNode)

        layer.add(tr);


        selectedNode = cloneNode;

        layer.draw()

        cloneNode.on('dragmove', () => {

            if (clonedRacewayText) {
                clonedRacewayText.x(cloneNode.x() + ((cloneNode.width() * cloneNode.scaleX()) - clonedRacewayText.width()) / 2);
                clonedRacewayText.y(cloneNode.y() + (cloneNode.height() * cloneNode.scaleY() / 2) - (clonedRacewayText.height()) / 2);
            }

            selectedNode = cloneNode
        })

        cloneNode.on('transform', () => {
            selectedNode = cloneNode
            if (cloneNode._id == selectedNodeId) {
                cloneDimenstionText.text(getTextDimensions(cloneNode));
            }

            if (clonedRacewayText) {
                clonedRacewayText.x(cloneNode.x() + ((cloneNode.width() * cloneNode.scaleX()) - clonedRacewayText.width()) / 2);
                clonedRacewayText.y(cloneNode.y() + (cloneNode.height() * cloneNode.scaleY() / 2) - (clonedRacewayText.height()) / 2);
            }

            let currentElementColorCost = getElementById(cloneNode._id).faceCostPerInch
            let clonedNodeHeight = (pxToIn(cloneNode.height() * cloneNode.scaleY()));
            let clonedNodeWidth = (pxToIn(cloneNode.width() * cloneNode.scaleX()));
            let clonedNodeTotalCost = costPerInch(clonedNodeWidth)
            let cloneNodeTotalColorCost = parseFloat(currentElementColorCost) * clonedNodeWidth
            let cloneNodePoints = [];

            if (contentHeight > clonedNodeWidth) {
                clonedNodeTotalCost = costPerInch(clonedNodeHeight)
                cloneNodeTotalColorCost = parseFloat(currentElementColorCost) * clonedNodeHeight
            }

            // if(cloneNode.getClassName() == 'Line') {
            //     cloneNodePoints = selectedNode.points() || []
            // }

            store.dispatch({
                type: 'UPDATE_ELEMENT', payload: {
                    'id': cloneNode._id,
                    'width': pxToIn(cloneNode.width() * cloneNode.scaleX()),
                    'height': pxToIn(cloneNode.height() * cloneNode.scaleY()),
                    'text': text,
                    'cost': clonedNodeTotalCost,
                    colorCost: cloneNodeTotalColorCost,
                    x: cloneNode.x(),
                    y: cloneNode.y(),
                    points: cloneNodePoints
                }
            });
        })




        cloneNode.on('click', e => {
            switch (cloneNode.getClassName()) {
                case 'RegularPolygon':
                    let triWidth = cloneNode.width() * cloneNode.scaleX() / triangleReduction
                    let triHeight = cloneNode.height() * cloneNode.scaleY() / triangleReduction
                    updateHeightWidthInput(triHeight, triWidth, 'triangle');

                    break;

                default:

                    updateHeightWidthInput(cloneNode.height() * cloneNode.scaleY(), cloneNode.width() * cloneNode.scaleX(), 'triangle');
                    break;
            }
        })

        layer.batchDraw();

        nodeLists.push({ type: 'text', node: cloneNode, id: currentElementIndex });
        updateHeightWidthDisplay()

        switch (cloneNode.getClassName()) {
            case 'RegularPolygon':
                let triWidth = cloneNode.width() * cloneNode.scaleX() / triangleReduction
                let triHeight = cloneNode.height() * cloneNode.scaleY() / triangleReduction
                let totalTriCost = costPerInch(pxToIn(triWidth));
                let totalTriColorCost = parseFloat(colorCost) * pxToIn(triWidth)
                if (triWidth < triHeight) {
                    totalTriCost = costPerInch(pxToIn(triHeight));
                    totalTriColorCost = parseFloat(colorCost) * pxToIn(triHeight)
                }
                store.dispatch({
                    type: 'ADD_ELEMENT', payload: {
                        'id': cloneNode._id,
                        'width': pxToIn(triWidth),
                        'height': pxToIn(triHeight),
                        'text': text,
                        'cost': totalTriCost,
                        'colorCost': totalTriColorCost,
                        faceCostPerInch: colorCost,
                        faceCostPerInch: colorCost,
                        x: cloneNode.x() - cloneNode.radius(),
                        y: cloneNode.y() + cloneNode.radius()
                    }
                });

                break;

            default:
                let widthInInch = pxToIn(cloneNode.width() * cloneNode.scaleX());
                let heightInInch = pxToIn(cloneNode.height() * cloneNode.scaleY());

                let totalCost = costPerInch(widthInInch)
                let totalColorCost = parseFloat(colorCost) * widthInInch
                if (widthInInch < heightInInch) {
                    totalCost = costPerInch(heightInInch)
                    totalColorCost = parseFloat(colorCost) * heightInInch
                }
                store.dispatch({
                    type: 'ADD_ELEMENT', payload: {
                        'id': cloneNode._id,
                        'width': pxToIn(cloneNode.width() * cloneNode.scaleX()),
                        'height': pxToIn(cloneNode.height() * cloneNode.scaleY()),
                        'text': text,
                        'cost': totalCost,
                        "colorCost": totalColorCost,
                        faceCostPerInch: colorCost,
                        x: cloneNode.x(),
                        y: cloneNode.y()

                    }
                });
                break;
        }

        if (selectedNode.getClassName() != 'Text') {
            currentElementIndex = currentElementIndex + 1;
        }
        selectedNode = cloneNode;

    })
    // undoBtn.addEventListener('click', function () {
    //     if (undoStack.length > 0) {
    //         const json = undoStack.pop();
    //         redoStack.push(stage.toJSON());
    //         loadState(json);
    //     }

    // });

    // redoBtn.addEventListener('click', function () {
    //     if (redoStack.length > 0) {
    //         const json = redoStack.pop();
    //         undoStack.push(stage.toJSON());
    //         loadState(json);
    //     }
    // });

    document.getElementById('zoomInBtn').addEventListener('click', function () {

        zoomStage(1.2);

    });

    document.getElementById('zoomOutBtn').addEventListener('click', function () {
        zoomStage(0.8);
    });

    deleteBtn.addEventListener('click', function () {

        if (selectedNode) {
            saveState();

            let lnIndex = null;
            let indexOfRItem = null;

            const index = nodeLists.findIndex(item => item.node._id === selectedNode._id);
            store.dispatch({
                type: 'REMOVE_ELEMENT', payload: { id: selectedNode._id }
            });
            nodeLists.splice(index, 1);

            let transformer = selectedNode.getAttr('transformer');
            if (transformer) {
                transformer.hide()
                transformer.destroy();
            }

            if (selectedNode.getAttr('textIndex')) {
                selectedNode.getAttr('textIndex').hide();
                selectedNode.getAttr('textIndex').destroy();
            }
            selectedNode.hide();
            selectedNode.destroy();

            selectedNode = null;
            layer.draw();
            updatePreview(null, null);
            updateHeightWidthDisplay();

        }
        updateHeightWidthInput(100, 100)
        updateHeightWidthDisplay();
        updateLeftsideBar();
        updateBottombarOverlay();

    });


    textInput.addEventListener('input', function () {
        let text = textInput.value
        if (selectedNode && selectedNodeType === 'Text') {
            selectedNode.text(text);
            updatePreview('text', selectedNode);

            if (text.length > 0) {
                let heightInInch = ((selectedNode.height() * selectedNode.scaleY()) / dpi)
                let widhtInInch = ((selectedNode.width() * selectedNode.scaleX()) / dpi)

                let currentElement = getElementById(selectedNode._id)
                let totalLength = ((selectedNode.text()).replace(' ', '')).length;
                let singleHeightInch = (selectedNode.height() * selectedNode.scaleY()) / dpi;
                let totalHeightInch = singleHeightInch * totalLength
                let totalColorCost = totalHeightInch * parseFloat(currentElement.faceCostPerInch);
                let dimenstionCost = (costPerInch(singleHeightInch) * totalLength).toFixed(1)
                store.dispatch({
                    type: 'UPDATE_ELEMENT',
                    payload: {
                        id: selectedNode._id,
                        height: heightInInch,
                        width: widhtInInch,
                        cost: dimenstionCost,
                        text,
                        colorCost: totalColorCost
                    }
                })
            } else {

                // deleteBtn.dispatchEvent(clickEvent)
            }

            updateDimenstionDisplay();
        }
        fontSize = parseFloat(sizeHeightInput.value) * dpi || 20;
        activeFontCode = (document.querySelector('.select-font').dataset.active).split('/')[1];
        activeFaceCode = (document.querySelector('.select-face').dataset.active).split('/')[1];
        activeTrimcapColorCode = (document.querySelector('.select-trimcap').dataset.active).split('/')[1];
        activeReturnColorCode = (document.querySelector('.select-return').dataset.active).split('/')[1];
        activeReturnSizeCode = parseFloat((document.querySelector('.select-return').dataset.activeSecond).split('/')[1]);
        activeTrimcapSizeCode = parseFloat((document.querySelector('.select-trimcap').dataset.activeSecond).split('/')[1])

        updateDimenstionDisplay()
        updateHeightWidthDisplay();
        selectedNode.width(undefined)

    });

    faceColorPicker.addEventListener('change', function () {
        faceColor = faceColorPicker.value;
        currentPreviewNode.setAttrs({
            fill: faceColor
        })
        updateNode(selectedNode, 'face-color');

    });

    trimCapColorPicker.addEventListener('change', function () {
        trimCapColor = trimCapColorPicker.value;

        currentPreviewNode.setAttrs({
            stroke: trimCapColor
        })
        updateNode(selectedNode, 'trimcap-color');

    });

    returnColorPicker.addEventListener('change', function () {
        returnColor = returnColorPicker.value;
        currentPreviewNode.setAttrs({
            shadowColor: returnColor
        })
        updateNode(selectedNode, 'return-color');

    });

    trimcapSizeInput.addEventListener('change', function () {
        let trimcapSize = parseFloat(trimcapSizeInput.value) || 3;
        currentPreviewNode.setAttrs({

            strokeWidth: trimcapSize
        })
        selectedNode.setAttrs({
            strokeWidth: trimcapSize
        })

    });
    returnSizeInput.addEventListener('change', function () {
        returnSize = parseFloat(returnSizeInput.value) || 5;
        currentPreviewNode.setAttrs({
            shadowOffsetX: returnSize,
            shadowOffsetY: returnSize,
        })
        updateNode(selectedNode, 'return-size');

    });

    sizeHeightInput.addEventListener('change', function () {
        if (selectedNode == null) {
            return;
        }

        let changeEvent = new CustomEvent('change', {
            bubbles: true,
            cancelable: true
        })

        const sizeInInches = parseFloat(sizeHeightInput.value);
        if (!isNaN(sizeInInches) && sizeInInches > 0) {

            let heightInInch = parseFloat(sizeHeightInput.value);
            let heightInPx = heightInInch * dpi;
            let widhtInPx = (selectedNode.width() * selectedNode.scaleX())
            let widthInInch = widhtInPx / dpi;
            let oldHeightInPx = selectedNode.height() * selectedNode.scaleY();

            switch (selectedNode.getClassName()) {
                case 'Text':

                    if (heightInInch > maxHeight) {
                        sizeHeightInput.value = maxHeight
                        return sizeHeightInput.dispatchEvent(changeEvent)
                    }
                    if (heightInInch < minHeight) {
                        sizeHeightInput.value = minHeight
                        return sizeHeightInput.dispatchEvent(changeEvent)
                    }


                    selectedNode.setAttrs({
                        fontSize: heightInPx
                    })

                    let text = selectedNode.text();

                    selectedNode.scaleX(1);
                    selectedNode.scaleY(1)
                    let scaleFactor = (heightInPx / oldHeightInPx)
                    selectedNode.width(undefined)
                    fontSize = selectedNode.fontSize() * selectedNode.scaleX();
                    heightInInch = pxToIn(selectedNode.height() * selectedNode.scaleY())
                    widthInInch = pxToIn(selectedNode.width() * selectedNode.scaleX())
                    store.dispatch({
                        type: 'UPDATE_ELEMENT',
                        payload: {
                            id: selectedNode._id,
                            width: widthInInch,
                            height: heightInInch,
                            cost: costPerInch(heightInInch) * (text.replace(' ', '') ? (text.replace(' ', '')).length : 1),
                            text,
                            colorCost: (heightInInch * (text.replace(' ', '')).length) * parseFloat(colorCost),
                            faceCostPerInch: parseFloat(colorCost),
                        }
                    })
                    setTextFSI(selectedNode._id, 'yes')

                    updateHeightWidthInput(null, widhtInPx, 'text')
                    triggerTransformEvent()

                    break;

                case 'Rect':

                    if (heightInInch < minHeight) {
                        sizeHeightInput.value = minHeight
                        return sizeHeightInput.dispatchEvent(changeEvent)
                    }
                    if (heightInInch > maxHeight) {
                        sizeHeightInput.value = maxHeight
                        return sizeHeightInput.dispatchEvent(changeEvent)
                    }


                    selectedNode.scaleX(1);
                    selectedNode.scaleY(1)
                    selectedNode.height(heightInPx)
                    updateHeightWidthInput(null, widhtInPx, 'text')

                    layer.draw()

                    let rectHeightInInch = pxToIn(selectedNode.height() * selectedNode.scaleY())
                    let rectWidthInInch = pxToIn(selectedNode.width() * selectedNode.scaleX())

                    let totalRectCost = costPerInch(rectHeightInInch)
                    let totalRectColorCost = (rectWidthInInch * parseFloat(colorCost));
                    if (rectHeightInInch > rectWidthInInch) {
                        totalRectCost = costPerInch(rectHeightInInch)
                        totalRectColorCost = (rectHeightInInch * parseFloat(colorCost));
                    }
                    store.dispatch({
                        type: 'UPDATE_ELEMENT',
                        payload: {
                            id: selectedNode._id,
                            width: rectWidthInInch,
                            height: rectHeightInInch,
                            cost: totalRectCost,
                            colorCost: totalRectColorCost,
                            faceCostPerInch: parseFloat(colorCost),
                        }
                    })
                    break;
                case 'Line':


                    if (heightInInch < minHeight) {
                        sizeHeightInput.value = minHeight
                        return sizeHeightInput.dispatchEvent(changeEvent)
                    }
                    if (heightInInch > maxHeight) {
                        sizeHeightInput.value = maxHeight
                        return sizeHeightInput.dispatchEvent(changeEvent)
                    }

                    let updatedPoints = updateArrowLine(heightInPx, null, 'height')
                    selectedNode.points(updatedPoints)

                    updateHeightWidthInput(null, widhtInPx, 'text')
                    triggerTransformEvent()
                    break;
                case 'RegularPolygon':
                    if (heightInInch < minHeight) {
                        sizeHeightInput.value = minHeight
                        return sizeHeightInput.dispatchEvent(changeEvent)
                    }
                    if (heightInInch > maxHeight) {
                        sizeHeightInput.value = maxHeight
                        return sizeHeightInput.dispatchEvent(changeEvent)
                    }

                    let oldHeight = ((selectedNode.radius() * 2)) / triangleReduction;

                    selectedNode.scaleY(heightInPx / oldHeight);
                    updateHeightWidthInput(null, widhtInPx / triangleReduction, 'text')

                    layer.draw()

                    let triHeightInInch = pxToIn(selectedNode.height() * selectedNode.scaleY()) / triangleReduction
                    let triWidthInInch = pxToIn(selectedNode.width() * selectedNode.scaleX()) / triangleReduction
                    let totalTriCost = costPerInch(triWidthInInch)
                    let totalTriColorCost = (triWidthInInch * parseFloat(colorCost))
                    if (triHeightInInch > triWidthInInch) {
                        totalTriCost = costPerInch(triHeightInInch)
                        totalTriColorCost = (triHeightInInch * parseFloat(colorCost))
                    }
                    store.dispatch({
                        type: 'UPDATE_ELEMENT',
                        payload: {
                            id: selectedNode._id,
                            width: triWidthInInch,
                            height: triHeightInInch,
                            cost: totalTriCost,
                            colorCost: totalTriColorCost,
                            faceCostPerInch: parseFloat(colorCost),
                        }
                    })
                    break;
                case 'Star':
                    if (heightInInch < minHeight) {
                        sizeHeightInput.value = minHeight
                        return sizeHeightInput.dispatchEvent(changeEvent)
                    }

                    if (heightInInch > maxHeight) {
                        sizeHeightInput.value = maxHeight
                        return sizeHeightInput.dispatchEvent(changeEvent)
                    }



                    let oldStarHeight = (selectedNode.attrs.outerRadius * 2)
                    selectedNode.scaleY(heightInPx / oldStarHeight)

                    updateHeightWidthInput(null, widhtInPx, 'text')
                    layer.draw()

                    let starHeightInInch = pxToIn(selectedNode.height() * selectedNode.scaleY())
                    let starWidthInInch = pxToIn(selectedNode.width() * selectedNode.scaleX())
                    let totalStarCost = costPerInch(starWidthInInch)
                    let totalStarColorCost = (starWidthInInch * parseFloat(colorCost))
                    if (starHeightInInch > starWidthInInch) {
                        totalStarCost = costPerInch(starHeightInInch)
                        totalStarColorCost = (starHeightInInch * parseFloat(colorCost))
                    }
                    store.dispatch({
                        type: 'UPDATE_ELEMENT',
                        payload: {
                            id: selectedNode._id,
                            width: starWidthInInch,
                            height: starHeightInInch,
                            cost: totalStarCost,
                            colorCost: totalStarColorCost,
                            faceCostPerInch: parseFloat(colorCost),
                        }
                    })

                    break;
                case 'Circle':
                    if (heightInInch < minHeight) {
                        sizeHeightInput.value = minHeight
                        return sizeHeightInput.dispatchEvent(changeEvent)
                    }
                    if (heightInInch > maxHeight) {
                        sizeHeightInput.value = maxHeight
                        return sizeHeightInput.dispatchEvent(changeEvent)
                    }


                    let oldCircleHeight = (selectedNode.radius() * 2)// * selectedNode.scaleY()
                    //selectedNode.scaleY(1)
                    selectedNode.scaleY(heightInPx / oldCircleHeight)

                    updateHeightWidthInput(null, widhtInPx, 'text')
                    layer.draw()

                    let circleHeightInInch = pxToIn(selectedNode.height() * selectedNode.scaleY())
                    let circleWidthInInch = pxToIn(selectedNode.width() * selectedNode.scaleX())
                    let totalCircleCost = costPerInch(circleWidthInInch)
                    let totalCircleColorCost = (circleWidthInInch * parseFloat(colorCost))
                    if (circleHeightInInch > circleWidthInInch) {
                        totalCircleCost = costPerInch(circleHeightInInch)
                        totalCircleColorCost = (circleHeightInInch * parseFloat(colorCost))
                    }
                    store.dispatch({
                        type: 'UPDATE_ELEMENT',
                        payload: {
                            id: selectedNode._id,
                            width: circleWidthInInch,
                            height: circleHeightInInch,
                            cost: totalCircleCost,
                            colorCost: totalCircleColorCost,
                            faceCostPerInch: parseFloat(colorCost),
                        }
                    })

                    break;

                default:
                    if (heightInInch < minHeight) {
                        sizeHeightInput.value = minHeight
                        return sizeHeightInput.dispatchEvent(changeEvent)
                    }
                    if (heightInInch > maxHeight) {
                        sizeHeightInput.value = maxHeight
                        return sizeHeightInput.dispatchEvent(changeEvent)
                    }
                    selectedNode.width(heightInPx);
                    selectedNode.height(heightInPx);


                    let shapeHeightInInch = pxToIn(selectedNode.height() * selectedNode.scaleY())
                    let shapeWidthInInch = pxToIn(selectedNode.width() * selectedNode.scaleX())
                    let totalShapeCost = costPerInch(shapeWidthInInch)
                    let totalShapeColorCost = (shapeWidthInInch * parseFloat(colorCost))
                    if (shapeHeightInInch > shapeWidthInInch) {
                        totalShapeCost = costPerInch(shapeHeightInInch)
                        totalShapeColorCost = (shapeHeightInInch * parseFloat(colorCost))
                    }
                    store.dispatch({
                        type: 'UPDATE_ELEMENT',
                        payload: {
                            id: selectedNode._id,
                            width: shapeWidthInInch,
                            height: shapeHeightInInch,
                            cost: totalShapeCost,
                            colorCost: totalShapeColorCost,
                            faceCostPerInch: parseFloat(colorCost),
                        }
                    })
                    updateHeightWidthInput(null, widhtInPx)

                    break;
            }


            if (selectedNode.getAttr('textIndex')) {
                let racewayText = selectedNode.getAttr('textIndex')

                racewayText.x(((selectedNode.x() + (selectedNode.width() * selectedNode.scaleX()) / 2)) - (racewayText.width() / 2));
                racewayText.y(selectedNode.y() + (((selectedNode.height() * selectedNode.scaleY()) / 2) - (racewayText.height() / 2)));
            }


        }



        updateLeftsideBar();
        updateHeightWidthDisplay();
    });


    sizeWidthInput.addEventListener('change', function (e) {

        if (selectedNode == null) {
            return;
        }
        let changeEvent = new CustomEvent('change', {
            bubbles: true,
            cancelable: true
        })
        let heightInInch = parseFloat(sizeHeightInput.value) || 1;
        let heightInPx = heightInInch * dpi;

        let widthInInch = parseFloat(sizeWidthInput.value) || 1;
        let widthInPx = widthInInch * dpi;


        let transformer = selectedNode.getAttr('transformer');

        let text = '';
        switch (selectedNode.getClassName()) {
            case 'Text':

                let currentWidth = selectedNode.width() * selectedNode.scaleX();
                let currentHeight = selectedNode.height() * selectedNode.scaleY();
                let currentFontSize = selectedNode.fontSize();

                if (pxToIn(currentHeight) > maxHeight) {
                    sizeHeightInput.value = maxHeight
                    return sizeHeightInput.dispatchEvent(changeEvent)
                }

                selectedNode.width(widthInPx)
                layer.batchDraw()


                let updatedHeight = selectedNode.height() * selectedNode.scaleY();
                let updatedWidth = selectedNode.width() * selectedNode.scaleX()

                let scaleFactor = (widthInPx / currentWidth);
                let newFontSize = getTextFSI(selectedNode._id) == 'yes' ? ((currentFontSize) * scaleFactor) - 1.3 : (currentFontSize) * scaleFactor

                selectedNode.fontSize(newFontSize)
                fontSize = newFontSize;

                let tr = selectedNode.getAttr('transformer');
                tr.update()

                triggerTransformEvent()
                selectedNode.scaleX(1);
                selectedNode.scaleY(1);
                layer.batchDraw()

                break;

            case 'Line':


                if (widthInInch < minWidth) {
                    sizeWidthInput.value = minWidth;
                    return sizeWidthInput.dispatchEvent(changeEvent)
                }

                if (widthInInch > maxWidth) {
                    sizeWidthInput.value = maxWidth;
                    return sizeWidthInput.dispatchEvent(changeEvent)
                }

                // Update the Konva line with the new points
                selectedNode.points(updateArrowLine(null, widthInPx, 'width'));


                layer.draw()
                triggerTransformEvent()

                let arrowWidth = (selectedNode.points()[6] - selectedNode.points()[0]) / dpi
                let arrowHeight = (selectedNode.points()[9] - selectedNode.points()[5]) / dpi
                let totalArrowCost = costPerInch(arrowWidth)
                let totalArrowColorCost = arrowWidth * parseFloat(colorCost)

                if (arrowHeight > arrowWidth) {
                    totalArrowCost = costPerInch(arrowHeight)
                    totalArrowColorCost = arrowHeight * parseFloat(colorCost)
                }

                store.dispatch({
                    type: 'UPDATE_ELEMENT', payload: {
                        'id': selectedNode._id,
                        text,
                        'type': selectedNode.getClassName(),
                        'cost': totalArrowCost,
                        'width': pxToIn(selectedNode.width() * selectedNode.scaleX()),
                        'height': pxToIn(selectedNode.height() * selectedNode.scaleY()),
                        colorCost: totalArrowColorCost,
                        faceCostPerInch: parseFloat(colorCost),
                    }
                });

                break;

            case 'Rect':

                if (selectedNode.getAttr('textIndex')) {

                    selectedNode.width(widthInPx * selectedNode.scaleX());
                    selectedNode.scaleX(1);
                    transformer.update();
                    sizeHeightInput.value = 8

                    triggerTransformEvent()
                } else {

                    if (widthInInch < minWidth) {
                        sizeWidthInput.value = minWidth;
                        return sizeWidthInput.dispatchEvent(changeEvent)
                    }

                    if (widthInInch > maxWidth) {
                        sizeWidthInput.value = maxWidth;
                        return sizeWidthInput.dispatchEvent(changeEvent)
                    }

                    let totalRectCost = costPerInch(widthInInch)
                    let totalRectColorCost = widthInInch * parseFloat(colorCost)
                    if (widthInInch < heightInInch) {
                        totalRectCost = costPerInch(heightInInch)
                        totalRectColorCost = heightInInch * parseFloat(colorCost)
                    }

                    selectedNode.width(widthInPx * selectedNode.scaleX());
                    selectedNode.height((sizeHeightInput.value * dpi) * selectedNode.scaleY());
                    selectedNode.scaleX(1);
                    //selectedNode.height(newSizeByHeight.height);
                    updateHeightWidthInput(selectedNode.height(), selectedNode.width())
                    selectedNode.scaleY(1);
                    transformer.update();
                    store.dispatch({
                        type: 'UPDATE_ELEMENT', payload: {
                            'id': selectedNode._id,
                            text,
                            'type': selectedNode.getClassName(),
                            'cost': totalRectCost,
                            width: widthInInch,
                            'height': pxToIn(selectedNode.height()),
                            colorCost: totalRectColorCost,
                            faceCostPerInch: parseFloat(colorCost)

                        }
                    });
                    triggerTransformEvent()

                }
                break;
            case 'Circle':

                if (widthInInch < minWidth) {
                    sizeWidthInput.value = minWidth;
                    return sizeWidthInput.dispatchEvent(changeEvent)
                }

                if (widthInInch > maxWidth) {
                    sizeWidthInput.value = maxWidth;
                    return sizeWidthInput.dispatchEvent(changeEvent)
                }

                let currentCircleWidth = selectedNode.radius() * 2;
                let newCircleWidth = widthInPx;
                selectedNode.scaleX(widthInPx / currentCircleWidth)
                layer.draw()

                let circleUpdatedHeight = selectedNode.height() * selectedNode.scaleY();
                let circleUpdatedWidth = selectedNode.width() * selectedNode.scaleX();

                let totalOvalCost = costPerInch(widthInInch)
                let totalOvalColorCost = widthInInch * parseFloat(colorCost)
                if (widthInInch < heightInInch) {
                    totalOvalCost = costPerInch(heightInInch)
                    totalOvalColorCost = heightInInch * parseFloat(colorCost)
                }
                store.dispatch({
                    type: 'UPDATE_ELEMENT', payload: {
                        'id': selectedNode._id,
                        text,
                        'type': selectedNode.getClassName(),
                        'cost': totalOvalCost,
                        'width': widthInInch,
                        'height': heightInInch,
                        colorCost: totalOvalColorCost,
                        faceCostPerInch: parseFloat(colorCost)

                    }
                });
                break;

            case 'RegularPolygon':

                if (widthInInch < minWidth) {
                    sizeWidthInput.value = minWidth;
                    return sizeWidthInput.dispatchEvent(changeEvent)
                }

                if (widthInInch > maxWidth) {
                    sizeWidthInput.value = maxWidth;
                    return sizeWidthInput.dispatchEvent(changeEvent)
                }

                var originalWidth = selectedNode.radius() * 2;

                var scaleX = (widthInPx) / (originalWidth / triangleReduction);
                selectedNode.scaleX(scaleX);
                layer.draw()

                let totalTriCost = costPerInch(widthInInch)
                let totalTriColorCost = widthInInch * parseFloat(colorCost)

                if (widthInInch < heightInInch) {
                    totalTriCost = costPerInch(heightInInch)
                    totalTriColorCost = heightInInch * parseFloat(colorCost)
                }

                store.dispatch({
                    type: 'UPDATE_ELEMENT', payload: {
                        'id': selectedNode._id,
                        text,
                        'type': selectedNode.getClassName(),
                        'cost': totalTriCost,
                        'width': widthInInch,
                        'height': heightInInch,
                        colorCost: totalTriColorCost,
                        faceCostPerInch: parseFloat(colorCost)

                    }
                });
                break;
            case 'Star':

                if (widthInInch < minWidth) {
                    sizeWidthInput.value = minWidth;
                    return sizeWidthInput.dispatchEvent(changeEvent)
                }

                if (widthInInch > maxWidth) {
                    sizeWidthInput.value = maxWidth;
                    return sizeWidthInput.dispatchEvent(changeEvent)
                }

                let currentStarWidth = selectedNode.attrs.outerRadius * 2;

                selectedNode.scaleX(widthInPx / currentStarWidth)
                layer.draw()

                let starHeightInch = pxToIn(selectedNode.height() * selectedNode.scaleY())
                let starWidthInch = pxToIn(selectedNode.width() * selectedNode.scaleX())

                let totalStarCost = costPerInch(starWidthInch)
                let totalStarColorCost = widthInInch * parseFloat(colorCost)
                if (starWidthInch < starHeightInch) {
                    totalStarCost = costPerInch(starHeightInch)
                    totalStarColorCost = starHeightInch * parseFloat(colorCost)
                }
                store.dispatch({
                    type: 'UPDATE_ELEMENT', payload: {
                        'id': selectedNode._id,
                        text,
                        'type': selectedNode.getClassName(),
                        'cost': totalStarCost,
                        'width': widthInInch,
                        'height': heightInInch,
                        colorCost: totalStarColorCost,
                        faceCostPerInch: parseFloat(colorCost)

                    }
                });


                break;

            default:


                if (widthInInch < minWidth) {
                    sizeWidthInput.value = minWidth;
                    return sizeWidthInput.dispatchEvent(changeEvent)
                }

                if (widthInInch > maxWidth) {
                    sizeWidthInput.value = maxWidth;
                    return sizeWidthInput.dispatchEvent(changeEvent)
                }

                let newSizeByHeight = maintainAspectRatio(widthInPx, null);
                selectedNode.width(newSizeByHeight.width);
                selectedNode.scaleX(1);
                selectedNode.height(newSizeByHeight.height);
                sizeHeightInput.value = pxToIn((newSizeByHeight.height).toFixed(1));
                updateHeightWidthInput(newSizeByHeight.height, newSizeByHeight.width)

                selectedNode.scaleY(1);
                transformer.update();

                let totalCost = costPerInch(widthInInch)
                let totalColorCost = widthInInch * parseFloat(colorCost)
                if (widthInInch < heightInInch) {
                    totalCost = costPerInch(heightInInch)
                    totalColorCost = heightInInch * parseFloat(colorCost)
                }

                store.dispatch({
                    type: 'UPDATE_ELEMENT', payload: {
                        'id': selectedNode._id,
                        text,
                        'type': selectedNode.getClassName(),
                        'cost': totalCost,
                        'width': pxToIn(selectedNode.width() * selectedNode.scaleX()),
                        'height': pxToIn(selectedNode.height() * selectedNode.scaleY()),
                        colorCost: totalColorCost,
                        faceCostPerInch: parseFloat(colorCost)

                    }
                });
                break;
        }


        if (selectedNode.getAttr('textIndex')) {
            let racewayText = selectedNode.getAttr('textIndex')

            racewayText.x(((selectedNode.x() + (selectedNode.width() * selectedNode.scaleX()) / 2)) - (racewayText.width() / 2));
            racewayText.y(selectedNode.y() + (((selectedNode.height() * selectedNode.scaleY()) / 2) - (racewayText.height() / 2)));
        }

        // if (selectedNode.getClassName() != 'RegularPolygon' && selectedNode.getClassName() != 'Circle' && selectedNode.getClassName() != 'Star') {
        //     triggerTransformEvent()
        // } else {
        //     store.dispatch({
        //         type: 'UPDATE_ELEMENT',
        //         payload: {
        //             id: selectedNode._id,
        //             height: pxToIn((selectedNode.height() * selectedNode.scaleY()) / triangleReduction),
        //             width: pxToIn((selectedNode.width() * selectedNode.scaleX()) / triangleReduction),
        //             cost: (costPerInch(pxToIn((selectedNode.width() * selectedNode.scaleX()) / triangleReduction))),
        //             colorCost: colorCost * costPerInch(pxToIn((selectedNode.width() * selectedNode.scaleX()) / triangleReduction))
        //         }
        //     })
        // }


        if (selectedNode.getClassName() == 'Text') {
            setTextFSI(selectedNode._id, 'no')
        }
        layer.batchDraw();
        updateLeftsideBar();
        updateHeightWidthDisplay();


    })

    cornerRadius.addEventListener('change', function (e) {
        if (selectedNode == null) return;
        let radius = parseFloat(e.target.value) * dpi;
        switch (selectedNode.getClassName()) {
            case 'RegularPolygon':

                selectedNode.setAttrs({
                    innerRadius: radius,
                    outerRadius: radius
                })
                layer.draw()

                break;

            case 'Star':

                break;

            case 'Rect':
                selectedNode.setAttrs({
                    cornerRadius: radius,
                })

                store.dispatch({
                    type: 'UPDATE_ELEMENT',
                    payload: {
                        'id': selectedNode._id,
                        'radius': radius
                    }
                })

                break;
        }
        layer.batchDraw()
    })


    bottomBarListItems.forEach(element => {
        element.addEventListener('click', function (e) {

            let type = element.dataset.type;
            let value = element.dataset.value;

            element.parentElement.querySelectorAll('.bottombar-list-item').forEach(element => {
                element.classList.remove('active')
            })

            this.classList.add('active')


            switch (type) {
                case 'ps':

                    document.querySelector('#powerSupply .current-item .value').innerHTML = '<b>' + capitalizeFirstLetter(value) + '</b>';

                    if (value == 'standard') {
                        store.dispatch({
                            type: 'UPDATE',
                            payload: {
                                'powerSupply': {
                                    'value': 'Standard',
                                    'cost': parseFloat(standarPsCost),
                                    'qty': 1
                                }
                            }
                        })

                    } else if (value == 'none') {
                        store.dispatch({
                            type: 'UPDATE',
                            payload: {
                                'powerSupply': {
                                    'value': 'None',
                                    'cost': parseFloat(0),
                                    'qty': 0
                                }
                            }
                        })
                    }

                    break;

                case 'lit':

                    document.querySelector('#ledLit .current-item .value').innerHTML = '<b>' + capitalizeFirstLetter(value) + '</b>';

                    if (value == 'front') {
                        document.querySelector('#ledLit .current-item .value').innerHTML = '<b>Front Lit</b>';
                        store.dispatch({
                            type: 'UPDATE',
                            payload: {
                                'lit': {
                                    'value': 'Front Lit',
                                    'cost': parseFloat(0),
                                    'qty': 1
                                }
                            }
                        })
                    } else if (value == 'both') {
                        document.querySelector('#ledLit .current-item .value').innerHTML = '<b>Front and Back Lit</b>';

                        store.dispatch({
                            type: 'UPDATE',
                            payload: {
                                'lit': {
                                    'value': 'Back Lit',
                                    'cost': parseFloat(backLitCost),
                                    'qty': 1
                                }
                            }
                        })

                    }
                    break;


                case 'cable':

                    if (value == '3') {
                        document.querySelector('#ledCable .current-item .value').innerHTML = '<b>3ft Cable</b>';
                        store.dispatch({
                            type: 'UPDATE',
                            payload: {
                                'cable': {
                                    'value': '3ft Cable',
                                    'cost': parseFloat(0),
                                    'qty': 1
                                }
                            }
                        })
                    } else if (value == '8') {
                        document.querySelector('#ledCable .current-item .value').innerHTML = '<b>8ft Cable</b>';

                        store.dispatch({
                            type: 'UPDATE',
                            payload: {
                                'cable': {
                                    'value': '8ft Cable',
                                    'cost': parseFloat(eightFtcableCost),
                                    'qty': 1
                                }
                            }
                        })

                    } else if (value == '0') {
                        document.querySelector('#ledCable .current-item .value').innerHTML = '<b>No Cable</b>';
                        store.dispatch({
                            type: 'UPDATE',
                            payload: {
                                'cable': {
                                    'value': 'No Cable',
                                    'cost': parseFloat(0),
                                    'qty': 0
                                }
                            }
                        })
                    }


                    ledCable
                    break;


            }


        });
    });


    document.querySelector('.select-font').addEventListener('click', e => {

        showLeftSlider(fontData, 'font', 1)
    })

    document.querySelector('.select-trimcap').addEventListener('click', e => {
        showLeftSlider(getDataById(['trimcap-color', 'trimcap-size'], productClData), 'trimcap', 1)
    })

    document.querySelector('.select-return').addEventListener('click', e => {
        let returnColorLength = getDataById(['return-color'], productClData)[0].options.length;
        if (returnColorLength > 20) {
            return showLeftSlider(getDataById(['return-color', 'return-size'], productClData), 'return', 4)

        }
        showLeftSlider(getDataById(['return-color', 'return-size'], productClData), 'return', 1)

    })
    document.querySelector('.select-face').addEventListener('click', e => {

        let faceColors = getDataById(['color-ac', 'color-3mt', 'color-3mb', 'color-3md', 'color-3mm'], productClData);

        let faceColorContainerLength = getDataById(['color-ac', 'color-3mt', 'color-3mb', 'color-3md', 'color-3mm'], productClData).length;
        let faceColorItemLength = getDataById(['color-ac', 'color-3mt', 'color-3mb', 'color-3md', 'color-3mm'], productClData)[0].options.length
        if (faceColorContainerLength > 0 && faceColorItemLength > 5) {
            return showLeftSlider(faceColors, 'face', 4)
        }
        showLeftSlider(faceColors, 'face', 1)



    })

    sliderCloseButton.addEventListener('click', e => {
        hideLeftSlider();
    })

    sidebarListItems.forEach(element => {
        let sliderItems = this.querySelectorAll('.slider-choose-item')

        sliderItems.forEach(sliderItem => {

        })
    });


    let editorContaier = document.getElementById('editorContainer').clientHeight;
    document.getElementById('dtContainer').style.height = editorContaier + 100 + 'px';

    window.addEventListener('keydown', function (e) {
        if (e.key === 'Delete') {
            let clickEvent = new CustomEvent('click', {
                bubbles: true,
                cancelable: true
            })

            deleteBtn.dispatchEvent(clickEvent)

        }
    });

    window.addEventListener('keydown', function (event) {

        if (selectedNode == null) return;
        if (document.activeElement == sizeWidthInput || document.activeElement == sizeHeightInput || document.activeElement == cornerRadiusInput) {
            return;
        }


        switch (event.key) {
            case 'ArrowUp':
                event.preventDefault();
                selectedNode.y(selectedNode.y() - 5)
                break;
            case 'ArrowDown':
                event.preventDefault();
                selectedNode.y(selectedNode.y() + 5)
                // Add your logic for "down" arrow key action
                break;
            case 'ArrowLeft':
                event.preventDefault();

                selectedNode.x(selectedNode.x() - 5)
                // Add your logic for "left" arrow key action
                break;
            case 'ArrowRight':
                event.preventDefault();


                selectedNode.x(selectedNode.x() + 5)
                // Add your logic for "right" arrow key action
                break;
        }
        updateHeightWidthDisplay()
        layer.draw()
    });


    saveBtn.addEventListener('click', async function (e) {



        let loadingImage = document.createElement('img');
        loadingImage.height = 20;
        loadingImage.style.marginLeft = '5px';
        loadingImage.src = siteUrl + '/wp-content/themes/wholesale/img/ajax_loader.gif';

        nodeLists.forEach(nodeContainer => {

            let node = nodeContainer.node;
            let transformer = node.getAttr('transformer')

            if (transformer) {
                transformer.destroy();

            }

            layer.draw()

        })

        this.appendChild(loadingImage);

        stage.height(container.clientHeight);

        var dataURL = stage.toDataURL({ mimeType: 'image/png' });
        let imageBlob = dataURLtoBlob(dataURL)

        // Prepare the form data
        var formData = new FormData();
        formData.append('file', imageBlob, 'clDesign-' + Math.random() + '.png');
        //formData.append('meta', "{'_custom_meta_key': 'custom_meta_value'}");
        formData.append('action', 'upload_image');

        // Optional, based on server setup

        try {

            // Make the AJAX request to upload the image
            const jwtToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL3N0b3JlZnJvbnRzaWdub25saW5lLmNvbSIsImlhdCI6MTczNDgxNTYyOSwibmJmIjoxNzM0ODE1NjI5LCJleHAiOjE3MzU0MjA0MjksImRhdGEiOnsidXNlciI6eyJpZCI6IjkifX19.swVU-Zp-lbpsLpXOLT97y9h4V5psnZrSyA5-gzWeVNw';
            const response = await fetch(siteUrl + '/wp-json/wp/v2/media', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${jwtToken}`,
                },
                body: formData,  // This contains the image data you're uploading
            })

            if (!response.ok) {
                console.error('Media upload failed:', response.statusText);
                return;
            }

            const mediaData = await response.json();
            const mediaId = mediaData.id; // Get the uploaded media ID

            console.log('Image uploaded successfully:', mediaData);
            this.querySelector('img').remove();
            let contentDimenstion = {
                height: contentHeight,
                width: contentWidth
            }
            let elements = store.getState()['elements']
            let extras = store.getState()['extras']
            let updatedState = { elements, extras, contentDimenstion }

            let designData = JSON.stringify(updatedState);
            let attachmentId = mediaData.id;
            let redirectUrl = `${productPermalink}?save_design=true&design_data=${designData}&design_id=${attachmentId}`;
            window.location.href = redirectUrl

        } catch (error) {
            console.error('Error uploading image:', error);
            this.querySelector('img').remove();
        }



    });



    container.addEventListener('click', function (e) {
        hideLeftSlider();
        const infoContents = document.querySelectorAll('.info-btn-content')
        infoContents.forEach((info) => {
            info.style.display = 'none';
        })

    })



    store.subscribe(() => {
        let totalCost = 0;
        let totalColorCost = 0;

        let elements = store.getState()['elements'];
        let extras = store.getState()['extras'];
        updateDetailTable(elements, extras)
        elements.forEach(element => {
            totalCost = totalCost + ((parseFloat(element.cost)));
            totalColorCost = totalColorCost + ((parseFloat(element.colorCost)));
        });

        let psCost = extras.powerSupply.qty > 0 ? extras.powerSupply.cost : 0;
        let litCostPecent = extras.lit && extras.lit.qty > 0 ? parseFloat(extras.lit.cost) : 0;
        let litCost = ((totalCost + totalColorCost) * litCostPecent) / 100
        let cableCost = extras.cable.qty > 0 ? extras.cable.cost : 0;

        detailTableBody.dataset.totalElementCost = parseFloat(totalCost + totalColorCost)
        totalCost = totalCost + psCost + litCost + cableCost;

        document.getElementById("displayCost").innerText = '$' + (totalCost + totalColorCost).toFixed(2);
        document.getElementById("dtTotalPriceDisplay").innerHTML = 'Price: <span class="text-success fw-bold">$' + (totalCost + totalColorCost).toFixed(2) + '</span>';

        let totalObjects = store.getState()['elements'].length;

        document.getElementById("dtTotalObjDisplay").innerHTML = 'Total : <span class="text-primary"> ' + totalObjects + ' </span> Objects';

        document.getElementById("dtTotalPriceDisplay").innerHTML = 'Total Price: <span class="text-success fw-bold">$' + (totalCost + totalColorCost).toFixed(2) + '</span>';

        document.querySelector("#totalObject .value").innerText = totalObjects
        console.log(store.getState());
    });
    console.log(editDesignElements);

    if (isEditDesign) {


        let clickEvent = new CustomEvent("click", { bubbles: true })
        editDesignElements.forEach(element => {

            let text = element.text || '';

            if (element.faceColor && element.faceColor.code) {
                faceColor = element.faceColor.code;
                activeFaceCode = element.faceColor.code;
                activeFaceTitle = element.faceColor.title;
            }
            if (element.returnColor && element.returnColor.code) {
                returnColor = element.returnColor.code;
                activeReturnColorCode = element.returnColor.code;
                activeReturnColorTitle = element.returnColor.title;
                updateActiveItem('return', activeReturnColorTitle + '/' + activeReturnColorCode)

            }

            if (element.trimcapColor && element.trimcapColor.code) {
                trimCapColor = element.trimcapColor.code;
                activeTrimcapColorCode = element.trimcapColor.code;
                activeTrimcapColorTitle = element.trimcapColor.title;
                updateActiveItem('trimcap', activeTrimcapColorTitle + '/' + activeTrimcapColorCode)
            }

            if (element.trimcapSize && element.trimcapSize.code) {
                trimCapSize = parseFloat(element.trimcapSize.code);
                activeTrimcapSizeCode = parseFloat(element.trimcapSize.code);
                activeTrimcapSizeTitle = element.trimcapSize.title;
                updateActiveItem('trimcap-size', activeTrimcapSizeTitle + '/' + activeTrimcapSizeCode)

            }

            if (element.returnSize && element.returnSize.code) {
                returnSize = parseFloat(element.returnSize.code);
                activeReturnSizeCode = parseFloat(element.returnSize.code)
                activeReturnSizeTitle = element.returnSize.title;
                updateActiveItem('return-size', activeReturnSizeTitle + '/' + activeReturnSizeCode)

            }

            if (element.font && element.font.code) {
                fontFamily = element.font.code;
                activeFontCode = element.font.code;
                activeFontTitle = element.font.title;
                updateActiveItem('font', activeFontTitle + '/' + activeFontCode)
            }

            if (element.fontSize) {
                fontSize = element.fontSize;
            }

            if (element.faceCostPerInch) {
                colorCost = parseFloat(element.faceCostPerInch)
            }

            let height = parseFloat(element.height) * dpi || false;
            let width = parseFloat(element.width) * dpi || false;
            let scaleX = element.scale.x || 1;
            let scaleY = element.scale.y || 1;
            let radius = element.radius || false;
            let points = element.points || false;


            switch (element.type) {
                case 'Text':

                    let textNodeId = addText(text, element.x, element.y, true);

                    store.dispatch({
                        type: 'UPDATE_ELEMENT',
                        payload: {
                            id: textNodeId,
                            ...element,
                            //colorCost : element.colorCost
                        }
                    })
                    setTextFSI(textNodeId, 'yes')
                    const clickEvent = new Event('click', {
                        bubbles: true,
                        cancelable: true,
                    });

                    if (width) {

                        if (selectedNode != null) {
                            selectedNode.width(undefined)

                        }

                    }

                    //document.querySelector('.select-font .slider-choose-item.active').dispatchEvent(clickEvent)
                    break;

                case 'Raceway':
                    addRaceway()
                    selectedNode.width(parseFloat(element.width) * dpi)
                    triggerTransformEvent()
                    if (height) {
                        selectedNode.height(height)

                    }
                    if (width) {
                        selectedNode.width(width)
                    }
                    break;

                case 'Rectangle':
                    addShape('rectangle')

                    if (height) {
                        selectedNode.height(height)

                    }

                    if (width) {
                        selectedNode.width(width)
                    }


                    break;

                case 'Circle':
                    addShape('circle')
                    if (height) {
                        selectedNode.height(height)

                    }

                    if (width) {
                        selectedNode.width(width)
                    }
                    //selectedNode.x(element.x)
                    //selectedNode.y(element.y)
                    break;
                case 'Triangle':
                    addShape('triangle')
                    if (height) {
                        selectedNode.height(height)

                    }

                    if (width) {
                        selectedNode.width(width)
                    }
                    break;
                case 'Starburst':
                    addShape('star')
                    if (height) {
                        selectedNode.height(height)

                    }

                    if (width) {
                        selectedNode.width(width)
                    }
                    break;
                case 'Arrow':
                    addShape('arrow')

                    if (height) {
                        selectedNode.height(height)

                    }

                    if (width) {
                        selectedNode.width(width)
                    }

                    if (points) {
                        selectedNode.points(points)
                    }

                    if (scaleX) {
                        selectedNode.scaleX(scaleX)
                    }

                    if (scaleY) {
                        selectedNode.scaleY(scaleY)

                    }

                    selectedNode.x(canvasWidth / 2)
                    selectedNode.y(canvasHeight / 2)
                    break;
                default:


                    break;
            }

            if (selectedNode) {
                selectedNode.x(parseFloat(element.x))
                selectedNode.y(parseFloat(element.y))

                if (selectedNode.getClassName() == 'Rect') {
                    if (radius) {
                        cornerRadius.value = radius
                        let changeEvent = new CustomEvent('change', {
                            bubbles: true,
                            cancelable: true
                        })
                        cornerRadius.dispatchEvent(changeEvent);
                        layer.batchDraw()
                    }
                }
            }


            triggerTransformEvent()
            layer.draw()

        })


        if (editDesignExtras) {
            let psValue = editDesignExtras.powerSupply.value.toLowerCase()
            let powerSupplySelector = document.querySelector('[data-type="ps"][data-value="' + psValue + '"]')
            powerSupplySelector && powerSupplySelector.dispatchEvent(clickEvent)

            let litValue = editDesignExtras.lit.value.toLowerCase()
            if (litValue == 'back lit') litValue = 'both';
            if (litValue == 'front lit') litValue = 'front';
            let litSelector = document.querySelector('[data-type="lit"][data-value="' + litValue + '"]')
            litSelector && litSelector.dispatchEvent(clickEvent)

            let cableValue = parseInt(editDesignExtras.cable.value.toLowerCase()) || 0
            let cableSelector = document.querySelector('[data-type="cable"][data-value="' + cableValue + '"]')
            cableSelector && cableSelector.dispatchEvent(clickEvent)
        }

    }

    // end script
})

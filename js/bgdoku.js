let searchAjax;
let counter = 0;
let searchPos;
let dailyDate;
let timeLeftTimer = 0;
let categories = [];
let dokuCounter = 10;
let correctCounter = 0;
let guessList = {};
let spoilerIsChecked =false;
let urlDoku = "https://www.bgdle.org/bgdoku.php";
let userToken;
let results = {};
let sortedAnswers = [];

function setupBGdoku(){
    for (let x = 1; x < 4; x++){
        for (let y = 1; y < 4; y++) {
            guessList["doku"+y.toString()+x.toString()] = null;
        }
    }
    //console.log(guessList);
    updateDate();
    updateCategories();
    setupEventListeners();
    setTimeout(() => {
        loadLocal();
        hideCurtains();
    }, 500);

}

function endGame(){
    for (let x=1; x < 4; x++){
        for (let y=1; y < 4; y++){
            document.getElementById("doku" + x + y).removeEventListener("click", toggleSearch);
        }
    }
    addRecord()
    saveLocal();

}

function addWinButton(){
    document.getElementById("winBtn").classList.remove("d-none");
}

function addRecord(){
    //console.log(userToken);
    let grid = [];
    for (let $y = 1; $y < 4; $y++){
        for(let $x = 1; $x < 4; $x++){
            let doku = guessList['doku'+$y+$x] ? guessList['doku'+$y+$x].id : "";
            grid.push(doku)
        }
    }
    $.ajax({
        type : "POST",  //type of method
        url  : "main.php",  //your page
        data : { recorddoku : dailyDate.replaceAll("-", ""), grid : grid, userToken: userToken },// passing the values
        success: function(res){
            //console.log(res);
            updateStatsBoard();

        }
    });
    /*
    searchAjax =  $.getJSON("main.php?recorddoku=" + dailyDate.replaceAll("-", "") +
        "&grid=" + JSON.stringify(grid),
        function (response) {
            console.log(response)
        });*/
}

function updateWinPage(){
    if(correctCounter>8){
        document.getElementById("winBackdropLabel").innerHTML = "You got them all!"
    }
    let guessCounter = document.getElementById("winGuessCounter")
    guessCounter.innerHTML = correctCounter.toString()
    let counterCol = document.getElementById("counterCol");
    counterCol.classList.add("d-none");
    let resultsToggle = document.getElementById("resultsToggle");
    resultsToggle.classList.remove("d-none");
    let winDate = document.getElementById("winDate");
    winDate.innerHTML = dailyDate;
    let winTotal = document.getElementById("winTotal");
    winTotal.innerHTML = results.total;
    if(results.total===1){
        document.getElementById("winTotalDiv").innerHTML = "You are the first person to finish a game today. You can come back later to see how others have done."
    }
    $("#winBackdrop").modal('show');
}

function updateStatsBoard(){
    $.getJSON("main.php?dailyStatsDoku=" + dailyDate.replaceAll("-", "") +"&token=" + userToken , function (data){
        //console.log(data);
        results = data;
        let list = sortStatList(data.answers);
        //console.log(list)
        sortedAnswers = list;
        for (let y = 1; y < 4; y++){
            for (let x = 1; x < 4; x++){
                let listPos = "doku"+y+x;
                if(!(listPos in list)){
                    continue;
                }
                let pos = "Stats"+y+x;
                let percent = 0;
                if(data.totals[listPos] !== undefined || data.totals[listPos] === 0){
                    percent = list[listPos][0]['count'] * 10000.0 / data.totals[listPos];
                }

                setupGameObject(pos, list[listPos][0], (Math.round(percent)/100)+"%");
            }
        }

        updateWinPage();
        addWinButton();
    })
}

function sortStatList(answers){
    let list = [];
    Object.keys(answers).forEach(cell => {
        let objValue = answers[cell]
        list[cell] = [];
        Object.keys(objValue).forEach(id => {
            let value = objValue[id];
            list[cell].push(value);
        })
        list[cell].sort(function (a, b){
            return b.count - a.count;
        })
    });
    return list;
}

function toggleWinPage(){
    $("#winBackdrop").modal('toggle');
}

function updateCategories(){
    console.log("Updating Categories...");
    searchAjax =  $.getJSON("main.php?dailydoku=" + dailyDate.replaceAll("-", ""),
        function (cats) {
        categories = cats;
        //console.log(cats);
        for (let i = 0; i<3;i++){
            let id = i+1;
            let side = i+3;
            createCategory(document.getElementById("doku0"+ id), cats[i]['type'], cats[i]['value'], cats[i]['description']);
            createCategory(document.getElementById("doku"+ id + "0"), cats[side]['type'], cats[side]['value'], cats[side]['description'], true);

            createCategory(document.getElementById("dokuStats0"+ id), cats[i]['type'], cats[i]['value'], cats[i]['description']);
            createCategory(document.getElementById("dokuStats"+ id + "0"), cats[side]['type'], cats[side]['value'], cats[side]['description'], true);
            $(function () {
                $('[data-bs-toggle="tooltip"]').tooltip()
            });
        }
    });
}

function createCategory(ele, type, value, description, left = false){
    ele.innerHTML = "";
    let cat = document.createElement("span")
    cat.classList.add("categoryHeader")
    ele.setAttribute("data-bs-toggle", "tooltip");
    ele.setAttribute("data-bs-title", description)
    if(left){
        ele.setAttribute("data-bs-placement", "left");
    }
    let inner = "";
    switch (type){
        case "designers":
            inner += "Designer: ";
            break;
        case "mechanics":
            inner += "Mechanic: ";
            break;
        case "categories":
            inner += "Category: ";
            break;
        case "artists":
            inner += "Artist: ";
            break;
        case "families":
            inner += "Family: ";
            break;
        default:
    }
    cat.innerHTML = inner;
    ele.appendChild(cat);
    ele.innerHTML += value;
}

function updateDate(forceChange = false){
    let ele = document.getElementById("title")
    if(forceChange || dailyDate === undefined){
        dailyDate = new Date().toJSON().slice(0, 10);

    }
    ele.innerHTML = "Daily: " + dailyDate;
    if(timeLeftTimer !== 0){
        clearInterval(timeLeftTimer);
    }
    timeLeftTimer = setInterval(updateTimeLeft, 1000);
}
function updateTimeLeft(){
    let ele = document.getElementById("timeLeft");
    let cd = new Date();
    let goalDate = new Date(Date.UTC(cd.getFullYear(), cd.getMonth(), cd.getDate() + 1));
    //console.log(cd + " - " + goalDate)
    let difference = goalDate-cd;
    if(difference < 0){
        difference += 1000*60*60*24;
    }
    let hours = Math.floor(difference / (1000*60*60));
    let mins = Math.floor((difference % (1000*60*60)) / (1000*60));
    let secs = Math.floor((difference % (1000*60)) / 1000)
    if(hours < 10){
        hours = "0" + hours;
    }
    if(mins < 10){
        mins = "0" + mins;
    }
    if(secs < 10){
        secs = "0" + secs;
    }

    //console.log(goalDate);
    ele.innerHTML = "Time Left: "+hours+":"+mins+":"+secs;
}

function hideCurtains(){
    let curtains = document.getElementById("curtain");
    curtains.classList.add("d-none");
}
function toggleWelcomeScreen(){
    $("#welcomeBackdrop").modal("toggle")
}

function togglePlayerResults(event){
    document.getElementById("boardStats").classList.add("d-none");
    document.getElementById("board").classList.remove("d-none");
    //console.log(guessList);
}

function toggleAllResults(event){
    document.getElementById("board").classList.add("d-none");
    document.getElementById("boardStats").classList.remove("d-none");
}

function toggleSearch(e){
    let searchbar = document.getElementById("searchBar");
    let pos = e.currentTarget.id.slice(-2);
    let posChars = pos.split('');
    let innerTop = document.getElementById("doku0"+posChars[1]).innerHTML;
    let innerSide = document.getElementById("doku"+posChars[0]+"0").innerHTML;
    document.getElementById("SearchTitleDescriptionTop").innerHTML = innerTop;
    document.getElementById("SearchTitleDescriptionSide").innerHTML = innerSide;

    searchbar.setAttribute("data-doku-pos", pos);
    if(e.currentTarget.id.includes("Stats")){
        searchbar.classList.add("d-none");
        setResults(pos);
    }
    $("#searchModal").modal("toggle")
}

function setupEventListeners() {
    let searchBar = document.getElementById("searchBar");
    searchBar.addEventListener("input", searchGames);
    searchBar.addEventListener("keyup", escapeSearch);
    document.getElementById("playDailyBtn").addEventListener("click", startDaily);
    document.getElementById("welcomeCloseButton").addEventListener("click", toggleWelcomeScreen);
    document.getElementById("helpBtn").addEventListener("click", toggleWelcomeScreen);
    document.getElementById("giveUpBtn").addEventListener("click", giveUp);
    document.getElementById("retryButton").addEventListener("click", retryDaily)
    document.getElementById("spoilerCheck").addEventListener("click", toggleSpoiler);
    document.getElementById("spoilerButton").addEventListener("click", createSpoilerForClipboard)
    document.getElementById("winBtn").addEventListener("click", toggleWinPage);
    document.getElementById("playerResultsToggle").addEventListener("change", togglePlayerResults)
    document.getElementById("allResultsToggle").addEventListener("change", toggleAllResults)
    document.getElementById("winCloseButton").addEventListener("click", toggleWinPage)
    document.getElementById("bgdleBtn").addEventListener("click", goToBgdle)
    //document.getElementById("spoilerDiscordButton").addEventListener("click", createSpoilerForClipboard)
    for (let x=1; x < 4; x++){
        for (let y=1; y < 4; y++){
            document.getElementById("doku" + x + y).addEventListener("click", toggleSearch);
            document.getElementById("dokuStats" + x + y).addEventListener("click", toggleSearch);
        }
    }

}

function goToBgdle(){
    window.open("http://www.bgdle.org", "_blank")
}

function setResults(pos){
    let posName= "doku"+pos;
    let percent = 0;
    //console.log(results.totals[posName]);
    if(results.total !== 0 && results.totals[posName] !== undefined) {
        percent = Math.round(results.totals[posName] * 10000 / results.total) / 100;

        //console.log(percent);
    }
    document.getElementById("searchTitle").innerHTML = percent + "% of people found a game.";
    //console.log(guessList);
    let id = guessList[posName] ? guessList[posName].id : 0;
    //console.log(id);
    setSearchedGames(sortedAnswers[posName], false, true, results.totals[posName], id);
}

function startDaily(){
    updateDate()
    toggleWelcomeScreen()
}

function retryDaily(){
    guessList = []
    dokuCounter = 10;
    saveLocal()
    location.reload()
}

function loadLocal(){
    if(localStorage.getItem("userTokenDoku") !== null){
        userToken = localStorage.getItem("userTokenDoku");
        checkToken(userToken, 0);
    }
    else {
        getToken();
    }
    if(localStorage.getItem("dailyDateDoku") === new Date().toJSON().slice(0, 10)){
        console.log("Loading Save...")
        dailyDate = localStorage.getItem("dailyDateDoku")


        //recordAdded = (localStorage.getItem("recordAdded") === "true") ;
        let guessJson= JSON.parse(localStorage.getItem("guessListDoku"));

        Object.entries(guessJson).forEach(([key,value]) =>{
            let pos = key.slice(-2);
            //console.log(key,value);
            if(value) {
                selectGame(value, pos, false);
            }
        })

        dokuCounter = parseInt(localStorage.getItem("dokuCounter"));
        if(isNaN(dokuCounter)){
            dokuCounter = 0;
        }
        updateDokuCounter();
        console.log("Loading Complete")
    } else {
        toggleWelcomeScreen()
    }
}

function saveLocal(){
    //le.log(JSON.stringify(guessList));
    localStorage.setItem("dailyDateDoku", dailyDate);
    localStorage.setItem("guessListDoku", JSON.stringify(guessList));
    localStorage.setItem("dokuCounter", dokuCounter.toString());
    //localStorage.setItem("recordAdded", recordAdded)
}

function checkToken(token, id){
    $.getJSON("main.php?loginToken=" + token + "&id=" + id, (reply) => {
        if(reply.status !== true){
            console.log("Ordering new Token!")
            getToken();
        }
    })
}

function getToken(){
    $.get("main.php?getToken", (tokenNew) => {
            userToken = tokenNew;
            localStorage.setItem("userTokenDoku",userToken);
    })
}

function toggleSpoiler(){
    let spoilerCheck = document.getElementById("spoilerCheck");
    if(spoilerIsChecked){
        spoilerCheck.classList.add("btnCheckOff");
        spoilerCheck.classList.remove("btnCheckOn");
        spoilerIsChecked = false;
        spoilerCheck.innerHTML = "Spoiler Off"
    } else {
        spoilerCheck.classList.remove("btnCheckOff");
        spoilerCheck.classList.add("btnCheckOn");
        spoilerIsChecked = true;
        spoilerCheck.innerHTML = "Spoiler On"
    }
}

function createSpoilerForClipboard(e){
    let clipBtn = e.currentTarget;
    let text = "BGdoku: Guessed the game in " + correctCounter + "/9 on " + dailyDate + " . Try your luck on " + urlDoku;
    /*if(spoilerIsChecked) {
        text += " \n!SPOILER! " + dailyDate + " guesses: "
        if (clipBtn.id === "spoilerDiscordButton") {
            text += "||";
        }
        guessList.forEach((guess) => {
            text += guess.name + " >>> ";
            if(guess.hints.length > 0){
                text += "[Hints Used: ";
                let first = true;
                guess.hints.forEach((hint) =>{
                    if(first){
                        first = false;
                        text += capitalizeFirstLetter(hint.attr) + ": " + hint.value;
                    } else {
                        text += ", " + capitalizeFirstLetter(hint.attr) + ": " + hint.value;
                    }
                })
                text += "] >>> ";
            }
        })
        text = text.slice(0, -4);
        if (clipBtn.id === "spoilerDiscordButton") {
            text += "||";
        }
    }*/
    navigator.clipboard.writeText(text);
    if(!clipBtn.classList.contains("greenBG")) {
        clipBtn.classList.add("greenBG");
        setTimeout(function () {
            clipBtn.classList.remove("greenBG");
        }, 3000);
    }
}

function clearSearch(){
    if(searchAjax !== undefined){
        searchAjax.abort();
    }
    let ele = document.getElementById("searchResultsDoku");
    ele.innerHTML = ""
    ele.classList.add("d-none")
    let search = document.getElementById("searchInput");
    search.value = "";
}

function escapeSearch(e){
    if(e.key === "Escape"){
        clearSearch();
    }
}

function searchGames(event){
    let searchstring = event.target.value;
    searchPos = event.currentTarget.getAttribute("data-doku-pos");
    if(searchstring===""){
        clearSearch()
        return
    }
    if(searchAjax !== undefined){
        searchAjax.abort();
    }
    searchAjax =  $.getJSON("main.php?searchdoku=" + searchstring, (json) => {
        //console.log(json);
        setSearchedGames(json)
    });
}

function setSearchedGames(games, selectable = true, forStats = false, total = 0, guessedGameID = 0){
    let ele = document.getElementById("searchResultsDoku");
    ele.innerHTML = ""
    ele.classList.add("d-none")
    games.forEach((game, i) =>{
        if(i === 0){
            ele.classList.remove("d-none")
        }
        let sameGame = false;
        if(forStats && guessedGameID === game.id){
            sameGame = true;
        }
        ele.appendChild(setSearchedGameElement(game, i, selectable, forStats, total, sameGame));
    })
}

function setSearchedGameElement(game, i, selectable, forStats, total, sameGame = false){
    let div = document.createElement("div")
    div.classList.add("row", "border-bottom", "gameDoku");
    div.id = "search" + i;
    if(selectable) {
        div.addEventListener("click", function () {
            selectGame(game, searchPos)
        })
    }
    let imgCol = setImage(div.id + "img", ["col-3", "searchGame", "centerItem"], game)
    div.appendChild(imgCol);
    let col = document.createElement("div")
    col.classList.add("col", "position-relative")
    div.appendChild(col)
    let titleRow = document.createElement("div")
    titleRow.classList.add("row")
    col.appendChild(titleRow)
    let title = document.createElement("div")
    title.classList.add("col", "h4")
    title.innerHTML = game.name
    titleRow.appendChild(title)
    let year = document.createElement("span")
    year.classList.add("h5")
    year.innerHTML = " (" + game.year + ")"
    title.appendChild(year)
    let pubRow = document.createElement("div")
    pubRow.classList.add("row")
    col.appendChild(pubRow)
    let publisher = document.createElement("div")
    publisher.classList.add("h6", "col")
    publisher.innerHTML = "by " + game.publisher;
    pubRow.appendChild(publisher)
    if(forStats){
        let percent = 0;
        if(total !== 0) {
            percent = Math.round(game.count * 10000 / total) / 100;
        }
        let onTopEle = document.createElement("span")
        onTopEle.classList.add("onTop");
        onTopEle.innerHTML =  percent + "%";
        col.appendChild(onTopEle);
        if(sameGame) {
            let icon = document.createElement("i");
            onTopEle.prepend(icon);
            icon.classList.add("bi-star-fill");
        }
    }
    return div
}

function setImage(id, classes, game, doku = []){
    let imgDiv = document.createElement("div");
    classes.forEach((cls) => {
        imgDiv.classList.add(cls);
    })
    let img = document.createElement("img");
    img.id = id;
    img.classList.add("img-fluid", "rounded");
    doku.forEach((cls) => {
        img.classList.add(cls);
    })
    img.src = "img/placeholder.png";
    //console.log(game)
    if (game != null){
        getImageUrl(id, game.id);
    }
    imgDiv.appendChild(img);
    return imgDiv;
}

function getImageUrl(eleID, gameID){
    $.get("main.php?imglink=" + gameID, function (data){
        updateImageSrc(eleID, data)
    })
}

function updateImageSrc(id, url){
    let img = document.getElementById(id);
    if(img){
        img.src = url;
    }
}

function selectGame(game, pos, save=true){
    clearSearch()

    counter++;
    let url = "main.php?dokuguess&game=" + game.id + "&date=" + dailyDate.replaceAll("-", "") + "&pos=" + pos;
    $.ajax({
        url: url,
        dataType: 'json',
        async: false,
        success: (json) => {

            //console.log(json);
            let posArray = pos.split("");
            if(json.result){
                guessList["doku"+pos] = game;
                document.getElementById("doku" + pos).removeEventListener("click", toggleSearch);
                setupGameObject(pos, game)
                correctCounter++;
            }
            else{
                if(json.topMistake) {
                    document.getElementById("doku0" + posArray[1]).classList.add("wrongFlash");
                }
                if(json.sideMistake){
                    document.getElementById("doku"+posArray[0]+"0").classList.add("wrongFlash");
                }
                setTimeout(() => {
                    document.getElementById("doku0"+posArray[1]).classList.remove("wrongFlash");
                    document.getElementById("doku"+posArray[0]+"0").classList.remove("wrongFlash");
                }, 1000);
            }
            $("#searchModal").modal("hide")
            dokuCounter--;
            updateDokuCounter();
            if(save){
                //console.log(guessList)
                saveLocal();
            }

        }
    })
    //console.log(guessList);
}

function giveUp(){
    updateDokuCounter(0);
}

function updateDokuCounter(value = dokuCounter){
    document.getElementById("dokuCounter").innerHTML = value.toString();
    dokuCounter = value;

    if(dokuCounter<1 || correctCounter > 8){
        endGame();
    }
}
function setupGameObject(pos, game=null, textOnTop = ""){
    let box = document.getElementById("doku" + pos);
    box.innerHTML = "";
    let img = setImage(pos + "image", [ "row", "dokuPanelImg", "centerItem", "imageCol"], game, ["dokuImg"])
    box.appendChild(img);
    let titleRowEle = setTitle( game);
    box.appendChild(titleRowEle);
    let onTopEle = document.createElement("span")
    onTopEle.classList.add("onTop");
    onTopEle.innerHTML = textOnTop;
    box.appendChild(onTopEle);
}

function setTitle(game){
    let titleH = document.createElement("div")
    titleH.classList.add("row", "dokuPanelTitle");
    let name = document.createElement("span");
    name.innerHTML = "?";
    if(game != null){
        name.innerHTML = game.name;
    }
    titleH.appendChild(name);
    titleH.innerHTML += " ";
    let year = document.createElement("span");
    year.innerHTML = "(????)";
    if(game != null){
        year.innerHTML = "(" + game.year + ")";
    }
    titleH.appendChild(year)

    return titleH;
}
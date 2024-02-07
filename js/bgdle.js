let dailyDate
let guessList = [];
let knownAnswers
let filterEle = "";
let filterObj = new class{}
filterObj.attr = ""
filterObj.value = ""
let filterUsed = true
let hints = 0
let loginUsername = ""
let signup = false;
let phpSession = "";
let recordID = 0;
let won = false;
let recordAdded = false;
let searchAjax;
let timeLeftTimer = 0;
function setupPage(){
    clearPage();
    setupGameObject("answer", "answerCol")
    updateDate()
    clearKnownAnswers()
    loadLocal()
    setupEventListeners()
    setupModalEvents()
    saveLocal()
    hideCurtains()
}

function hideCurtains(){
    let curtains = document.getElementById("curtain");
    curtains.classList.add("d-none");
}

function setupEventListeners(){
    let searchBar = document.getElementById("searchBarCol");
    searchBar.addEventListener("input", searchGames)
    searchBar.addEventListener("keyup", escapeSearch)
    document.getElementById("playDailyBtn").addEventListener("click", startDaily)
    document.getElementById("welcomeCloseButton").addEventListener("click", toggleWelcomeScreen)
    document.getElementById("helpBtn").addEventListener("click", toggleWelcomeScreen)
    document.getElementById("legendTab").addEventListener("click", toggleLegend)
    document.getElementById("retryButton").addEventListener("click", retryDaily)
    document.getElementById("spoilerButton").addEventListener("click", createSpoilerForClipboard)
    document.getElementById("spoilerDiscordButton").addEventListener("click", createSpoilerForClipboard)
    document.getElementById("loginTitleBtn").addEventListener("click", loginOrOut)
    document.getElementById("winLoginButton").addEventListener("click", loginOrOut)
    document.getElementById("loginCloseButton").addEventListener("click", loginOrOut)
    document.getElementById("submitLoginBtn").addEventListener("click", submitLogin)
    document.getElementById("changeLoginBtn").addEventListener("click", ()=>{toggleLoginChange()})
    document.getElementById("winBtn").addEventListener("click", toggleWinPage);
    document.getElementById("winCloseButton").addEventListener("click", toggleWinPage)
    document.getElementById("spoilerCheck").addEventListener("click", toggleSpoiler);

}

function setupModalEvents(){

}

function escapeSearch(e){
    if(e.key === "Escape"){
        clearSearch();
    }
}

function submitLogin(){
    let usernameField = document.getElementById("usernameField")
    let username = usernameField.value;
    let passwordField = document.getElementById("passwordField")
    let password = passwordField.value;
    let emailField = document.getElementById("emailField");
    let email = emailField.value;
    let eleError = document.getElementById("loginError")
    if(username.includes("'") || username.includes('"') || username.includes(' ')){
        alert("Username can not contain single, double quotes or spaces");
        return
    }
    if(password.includes("'") || password.includes('"') || password.includes(' ')){
        alert("Password can not contain single or double quotes or spaces");
        return
    }
    if(email.includes("'") || email.includes('"') || email.includes(' ')){
        alert("Email can not contain single or double quotes or spaces");
        return
    }
    if(username.length < 4 || password.length < 4){
        alert("Username and password needs to be at least 4 characters long. Recommended at least 8 in each.")
        return
    }
    //console.log("'" + username + "' - '" + password + "' - '" + email + "' - Signup: " + signup)
    $.get("main.php?login=" + signup + "&username=" + username + "&password=" + password +
            "&email=" + email, function (reply) {
        if(reply !== ""){
            loginUsername = username;
            phpSession = reply;
            usernameField.value = "";
            password.value = "";
            email.value = "";
            eleError.innerHTML = "";
            saveLocal();
            updateLogin()
            toggleLogin();
            toggleWinLoginRow();
            if(won){
                addRecord();
                toggleWinPage()
            }
        } else {
            if(signup){
                eleError.innerHTML = "Username already taken.";

            } else {
                eleError.innerHTML = "No Username/Password combo found.";
            }
        }
    })
}

function updateLogin() {
    let usernameTitle = document.getElementById("usernameTitle")
    usernameTitle.innerHTML = "Hi " + loginUsername;
}
function toggleLoginChange(reset = false){
    let loginTitle = document.getElementById("loginTitle");
    let changeLoginBtn = document.getElementById("changeLoginBtn");
    let submitLoginBtn = document.getElementById("submitLoginBtn");
    let emailRow = document.getElementById("emailRow")
    if(reset || loginTitle.innerHTML !== "Login"){
        loginTitle.innerHTML = "Login"
        changeLoginBtn.innerHTML = "Sign-up instead"
        submitLoginBtn.innerHTML = "Login"
        emailRow.classList.add("d-none")
        signup=false;
    } else {
        loginTitle.innerHTML = "Sign-Up"
        changeLoginBtn.innerHTML = "Login instead"
        submitLoginBtn.innerHTML = "Sign-Up"
        emailRow.classList.remove("d-none")
        signup=true;
    }
}
function loginOrOut(){
    if(loginUsername !== ""){
        //logout
        loginUsername = "";
        phpSession = "";
        let loginIcon = document.getElementById("loginIcon");
        loginIcon.classList.remove("bi-box-arrow-left");
        loginIcon.classList.add("bi-box-arrow-in-right")
        let userTitle = document.getElementById("usernameTitle");
        userTitle.innerHTML = "";
        saveLocal()
        toggleWinLoginRow(true);
    }
    else{
        toggleLoginChange(true)
        toggleLogin();
    }
}

function toggleWinLoginRow(forceLogoutRow){
    let loginRow = document.getElementById("winLogin")
    let loggedRow = document.getElementById("winLoggedIn")
    let loginButton = document.getElementById("winLoginButton")
    if(forceLogoutRow || loginRow.classList.contains("d-none")){
        loginRow.classList.remove("d-none");
        loggedRow.classList.add("d-none");
        loginButton.classList.remove("d-none");
    } else {
        loggedRow.classList.remove("d-none");
        loginRow.classList.add("d-none");
        loginButton.classList.add("d-none");
    }
}

function toggleLogin(){
    $("#loginBackdrop").modal('toggle');
}

function retryDaily(){
    guessList = []
    hints = 0
    saveLocal()
    location.reload()
}

function startDaily(e, randomDate = false){
    if(randomDate){
        dailyDate = Math.floor(1 + Math.random() * 1000);
    }
    updateDate()
    toggleWelcomeScreen()
}

function saveLocal(){
    localStorage.setItem("dailyDate", dailyDate);
    localStorage.setItem("guessList", JSON.stringify(guessList));
    localStorage.setItem("hints", hints.toString());
    localStorage.setItem("phpSession", phpSession)
    localStorage.setItem("recordAdded", recordAdded)
}

function loadLocal(){
    if(localStorage.getItem("dailyDate") === new Date().toJSON().slice(0, 10)){
        console.log("Loading...")
        dailyDate = localStorage.getItem("dailyDate")
        hints = parseInt(localStorage.getItem("hints"));
        if(isNaN(hints)){
            hints = 0;
        }
        updateHintCounter(0);

        phpSession = localStorage.getItem("phpSession");
        getUserFromSession();
        recordAdded = (localStorage.getItem("recordAdded") === "true") ;
        let guessJson= JSON.parse(localStorage.getItem("guessList"));
        guessJson.forEach((guess) =>{
            selectGame(guess);
        })
    } else {
        toggleWelcomeScreen()
    }
}

function getUserFromSession(){
    $.get("main.php?session=" + phpSession, (reply) => {
        if(reply !== ""){
            loginUsername = reply;
            updateLogin();
            getRecords();
            toggleWinLoginRow();
        }
    })
}

function toggleWelcomeScreen(){
    $("#welcomeBackdrop").modal("toggle")
}

function toggleLegend(){
    let ele = document.getElementById("legend")
    if(ele.classList.contains("showLegend")){
        ele.classList.remove("showLegend");
    }
    else {
        ele.classList.add("showLegend")
    }
}
function clearKnownAnswers(){
    knownAnswers = new class{};
    knownAnswers.categories = [];
    knownAnswers.categoriesPercent = 0;
    knownAnswers.mechanics = [];
    knownAnswers.mechanicsPercent = 0;
    knownAnswers.designers = [];
    knownAnswers.designersPercent = 0;
    knownAnswers.artists = [];
    knownAnswers.artistsPercent = 0;
    knownAnswers.year = "";
    knownAnswers.minplayers = "";
    knownAnswers.maxplayers = "";
    knownAnswers.minplaytime = "";
    knownAnswers.maxplaytime = "";
    knownAnswers.minage = "";
    knownAnswers.publisher = "";
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

function clearPage(){
    let answer = document.getElementById("answerCol");
    answer.innerHTML = "";
    let guesses = document.getElementById("guessesCol")
    guesses.innerHTML = "";
}

function clearSearch(){
    if(searchAjax !== undefined){
        searchAjax.abort();
    }
    let ele = document.getElementById("searchResults");
    ele.innerHTML = ""
    ele.classList.add("d-none")
    let search = document.getElementById("searchInput");
    search.value = "";
}

function searchGames(event){
    let searchstring = event.target.value;
    if(searchstring===""){
        clearSearch()
        return
    }
    if(filterObj.attr === "" || filterObj.value === "") {
        if(searchAjax !== undefined){
            searchAjax.abort();
        }
        searchAjax =  $.getJSON("main.php?search=" + searchstring, setSearchedGames);
    }
    else{

        if(!filterUsed){
            filterUsed = true;
            updateHintCounter(1);
            guessList[guessList.length - 1].hints.push({attr:filterObj.attr, value:filterObj.value});
            console.log(guessList);
        }
        let url = "main.php?searchadv=" + searchstring;
        url += "&attr=" + filterObj.attr;
        url += "&value=" + filterObj.value;
        //console.log(url)
        if(searchAjax !== undefined){
            searchAjax.abort();
        }
        searchAjax =  $.getJSON(url , setSearchedGames);
    }
}

function updateHintCounter(increase){
    hints += increase;
    let hintCounter = document.getElementById("hintCounter");
    //console.log(hints);
    hintCounter.innerHTML = "H:" + hints;
    let winHint = document.getElementById("winHints");
    winHint.innerHTML = "(" + hints + " hints used)";
    if(hints === 0){
        hintCounter.innerHTML = "";
        winHint.innerHTML = "";
    }
}

function setSearchedGames(games){
    let ele = document.getElementById("searchResults");
    ele.innerHTML = ""
    ele.classList.add("d-none")
    games.forEach((game, i) =>{
        if(i === 0){
            ele.classList.remove("d-none")
        }
        ele.appendChild(setSearchedGameElement(game, i));
    })
}

function setSearchedGameElement(game, i){
    let div = document.createElement("div")
    div.classList.add("row", "border-bottom");
    div.id = "search" + i;
    div.addEventListener("click", function (){ selectGame(game)})
    let imgCol = setImage(div.id + "img", ["col-3", "searchGame", "centerItem"], game)
    div.appendChild(imgCol);
    let col = document.createElement("div")
    col.classList.add("col")
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
    return div
}

function selectGame(game){
    clearSearch()
    if(won){
        return;
    }
    let nonUnique = false;
    guessList.forEach((guess) => {
        if(guess.id === game.id){
            nonUnique = true;
        }
    })
    if(nonUnique){
        return
    }
    game.hints = [];
    guessList.push(game)
    let counter = guessList.length - 1;
    let url = "main.php?request&game=" + game.id + "&date=" + dailyDate.replaceAll("-", "");
    $.ajax({
        url: url,
        dataType: 'json',
        async: false,
        success: (json) => {
            setupGameObject("guess" + counter, "guessesCol", game)
            compareGame(json, counter)
        }
    })
    //console.log(guessList);
}

function compareGame(comparisonJson, guessCounter){
    let game = guessList[guessCounter];
    updateGuessCounter(guessCounter + 1);
    determineColorAndIcon("year", comparisonJson.year, "guess" + guessCounter + "year");
    determineColorAndIcon("minplayers", comparisonJson.minplayers, "guess" + guessCounter + "minplayers");
    determineColorAndIcon("maxplayers", comparisonJson.maxplayers, "guess" + guessCounter + "maxplayers");
    determineColorAndIcon("minplaytime", comparisonJson.minplaytime, "guess" + guessCounter + "minplaytime");
    determineColorAndIcon("maxplaytime", comparisonJson.maxplaytime, "guess" + guessCounter + "maxplaytime");
    determineColorAndIcon("minage", comparisonJson.minage, "guess" + guessCounter + "minage");
    determineColorAndIcon("publisher", comparisonJson.publisher, "guess" + guessCounter + "publisher");
    determineColorAndIcon("mechanics", comparisonJson.mechanics, "guess" + guessCounter, game.mechanics,
        "mechanic", comparisonJson.mechanicsTotal);
    determineColorAndIcon("categories", comparisonJson.categories, "guess" + guessCounter, game.categories,
        "category", comparisonJson.categoriesTotal);
    determineColorAndIcon("artists", comparisonJson.artists, "guess" + guessCounter, game.artists,
        "artist", comparisonJson.artistsTotal);
    determineColorAndIcon("designers", comparisonJson.designers, "guess" + guessCounter, game.designers,
        "designer", comparisonJson.designersTotal);
    checkWin(guessCounter + 1);
    saveLocal()
}

function updateGuessCounter(){
    let ele = document.getElementById("guessCounter")
    ele.innerHTML = guessList.length.toString()
}

function checkWin(guessCounter){
    //console.log(knownAnswers)
    if(knownAnswers.categoriesPercent < 100 || knownAnswers.mechanicsPercent < 100 ||
        knownAnswers.designersPercent < 100 ||knownAnswers.artistsPercent < 100 ){
        checkUnveilTitle();
        return
    }
    if(knownAnswers.year === "" || knownAnswers.minplayers === "" || knownAnswers.maxplayers === "" ||
        knownAnswers.minplaytime === "" || knownAnswers.maxplaytime === "" || knownAnswers.minage === "" ||
        knownAnswers.publisher === "" ) {
        checkUnveilTitle();
        return
    }
    win(guessCounter);
}
function win(guessCounter){
    won = true;
    getImageUrl("answerimage", guessList[guessList.length-1].id)
    updateName("answername", guessList[guessList.length-1].name, guessList[guessList.length-1].id)
    updateWinPage(guessCounter);
    addRecord()
    addWinButton();
}

function addRecord(){
    if(!recordAdded || loginUsername !== "") {
        $.get("main.php?record=" + recordID + "&session=" + phpSession + "&date=" + getConvertedDate() +
            "&guesses=" + guessList.length + "&hints=" + hints, (id) => {
            recordID = id;
            recordAdded = true;
            getRecords()
        })
    }
}

function getRecords(){
    $.getJSON("main.php?records="+phpSession, (rows)=>{
        //console.log(rows);
        let avgHint = 0;
        let total = rows.length;
        let streak = 0;
        let avgGuess = 0;
        let lastDate = datefy(rows[0]['date']);
        rows.forEach((record) =>{
            //console.log(lastDate - datefy(record['date']) <= (1000 * 60 * 60 * 24))
            if((lastDate - datefy(record['date'])) <= (1000 * 60 * 60 * 24)){
                streak++;
            } else {
                streak = 1;
            }
            lastDate = datefy(record['date']);
            avgGuess += parseInt(record['guesses']);
            avgHint += parseInt(record['hints']);
        })
        avgGuess = Math.round((avgGuess / total) * 100)/100;
        avgHint = Math.round((avgHint / total) * 100)/100;
        updateWinRecordsRow(total, streak, avgGuess, avgHint);
    })
}

function datefy(date){
    return Date.parse(date.substring(0,4) + "-" + date.substring(4,6) + "-" + date.substring(6))
}

function updateWinRecordsRow(total, streak, guess, hint){
    let eleTotal = document.getElementById("winTotal");
    let eleStreak = document.getElementById("winStreak");
    let eleAvgGuess = document.getElementById("winAvgGuesses");
    let eleAvgHint = document.getElementById("winAvgHints");
    eleTotal.innerHTML = total;
    eleStreak.innerHTML = streak;
    eleAvgGuess.innerHTML = guess;
    eleAvgHint.innerHTML = "(" + hint + ")";

}

function getConvertedDate(){
    return dailyDate.replaceAll("-", "")
}

function checkUnveilTitle(){
    if(guessList.length % 10 === 0){
        let letterPos = Math.floor(guessList.length / 10) - 1;
        $.get("main.php?unveilTitle=" + letterPos + "&date=" + getConvertedDate(), function (letter){
            if(won){
                return;
            }
            let ele = document.getElementById("answername");
            ele.innerHTML = letter + ele.innerHTML;
        })
    }
}


function updateWinPage(counter){
    let guessCounter = document.getElementById("winGuessCounter")
    guessCounter.innerHTML = counter.toString()
    let winDate = document.getElementById("winDate");
    winDate.innerHTML = dailyDate;
    $("#winBackdrop").modal('show');
}

function toggleWinPage(){
    $("#winBackdrop").modal('toggle');
}

function addWinButton(){
    document.getElementById("winBtn").classList.remove("d-none");
}
function updateName(id, value, gameID){
    let ele = document.getElementById(id);
    let url = "https://boardgamegeek.com/boardgame/" + gameID;
    ele.innerHTML = "<a href='" + url +"' target='_blank'>"+ value + "</a>";
}

function determineColorAndIcon(attribute, comparison, eleID, array = null, arrElePrefix = "", arrCompletion = 0){
    if(Array.isArray(comparison) && Array.isArray(array)){
        array.forEach((value, id) => {
            if(comparison.includes(value)){
                updateColorAndIcon(eleID + arrElePrefix + id, Comparison.SAME)
                checkAnswerArray(attribute, value, arrCompletion)
            }
        })
    }
    else if(attribute === "publisher"){
        if(comparison){
            //console.log("here")
            updateColorAndIcon(eleID, Comparison.SAME)
            checkAnswer(attribute)
        }
    }
    else {
        updateColorAndIcon(eleID, comparison)
        if(comparison === Comparison.SAME){
            checkAnswer(attribute)
        }
    }
}

function updateColorAndIcon(eleID, comparison){
    let ele = document.getElementById(eleID)
    let icon;
    switch (comparison) {
        case Comparison.A_LOT_LOWER:
            ele.classList.add("lower")
            icon = document.createElement("i");
            ele.prepend(icon);
            icon.classList.add("bi-chevron-double-down");
            break;
        case Comparison.LOWER:
            ele.classList.add("lower")
            icon = document.createElement("i");
            ele.prepend(icon);
            icon.classList.add("bi-chevron-down");
            break;
        case Comparison.SAME:
            ele.classList.add("correct")
            icon = document.createElement("i");
            ele.prepend(icon);
            icon.classList.add("bi-check");
            break;
        case Comparison.HIGHER:
            ele.classList.add("higher")
            icon = document.createElement("i");
            ele.prepend(icon);
            icon.classList.add("bi-chevron-up");
            break;
        case Comparison.A_LOT_HIGHER:
            ele.classList.add("higher")
            icon = document.createElement("i");
            ele.prepend(icon);
            icon.classList.add("bi-chevron-double-up");
            break;
        default:
    }
}

function checkAnswer(attr){
    if(knownAnswers[attr] !== guessList[guessList.length-1][attr]) {
        knownAnswers[attr] = guessList[guessList.length - 1][attr]
        updateAnswer(attr, "answer" + attr, knownAnswers[attr]);
    }
}

function checkAnswerArray(attribute, value, arrCompletion){
    let array = knownAnswers[attribute];
    let target = "answer" + attribute;
    let prog = attribute + "bar";
    if(!array.includes(value)){
        array.push(value)
        knownAnswers[attribute + "Percent"] = array.length / arrCompletion * 100;
        let percent = knownAnswers[attribute + "Percent"];
        updateAnswerArray(attribute, value, target, array, prog, percent)
    }
}

function updateAnswer(attr, eleID, value){
    let ele = document.getElementById(eleID);
    ele.innerHTML = value;
    ele.classList.add("correct")
    ele.title = "Filter by " + attr + ": " + value;
    if(eleID === "answeryear"){
        ele.innerHTML = "(" + value + ")";
    }
    ele.addEventListener("click",(event) =>{
        setFilter(event.target, attr, value)
    })
}

function updateAnswerArray(attribute, value, eleID, array, prog, percent){
    let ele = document.getElementById(eleID);
    let span = document.createElement("span");
    span.addEventListener("click", (event) =>{
        setFilter(event.target, attribute, value)
    })
    span.title = "Filter by " + attribute + ": " + value;
    let textValue = "" + value;
    if(array.length > 1){
        textValue=  ", " + textValue;
    }
    span.classList.add("correct")
    span.innerHTML = textValue;
    ele.appendChild(span);

    let progEle = document.getElementById(prog);
    progEle.style.width = percent + "%"
    progEle.innerHTML = Math.round(percent) + "%"
}

function setFilter(ele, attribute, value){
    //console.log(attribute + " = " + value + " - " + ele)
    let searchbar = document.getElementById("searchInput")
    if(ele === filterEle){
        filterEle = "";
        ele.classList.remove("filter")
        searchbar.placeholder = "Enter board game name here...";
        filterUsed = true
        filterObj.attr = "";
        filterObj.value = "";
    }
    else {
        if(filterEle !== ""){
            filterEle.classList.remove("filter")
        }
        filterEle = ele;
        filterEle.classList.add("filter")
        searchbar.placeholder = "[Filter " + attribute + ": " + value + "] Enter board game name here...";
        filterUsed = false
        filterObj.attr = attribute;
        filterObj.value = value;
    }

}

function setupGameObject(idPrefix, boxId, game=null){
    let box = document.getElementById(boxId);
    let gameEle = document.createElement("div");
    gameEle.classList.add("row", "rounded", "gameBox");
    gameEle.id = idPrefix;
    box.prepend(gameEle);
    let img = setImage(idPrefix + "image", [ "col-md-4", "centerItem", "col-lg-2", "cellBorderBot", "cellBorderRight", "cellBorderLeft"], game)
    gameEle.appendChild(img);
    let titleBox = document.createElement("div");
    titleBox.classList.add("col-md-8", "col-lg-3", "d-flex", "flex-column");
    gameEle.appendChild(titleBox);
    let titleRow = document.createElement("div")
    titleRow.classList.add("row", "cellBorderBot")
    titleBox.appendChild(titleRow);
    let title = setTitle(idPrefix, game);
    titleRow.appendChild(title);
    let secondRow = document.createElement("div");
    secondRow.classList.add("row");
    titleBox.appendChild(secondRow)
    let players = setPlayers(idPrefix, game);
    secondRow.appendChild(players)
    let minAge = setMinAge(idPrefix, game);
    secondRow.appendChild(minAge)
    let thirdRow = document.createElement("div")
    thirdRow.classList.add("row", "flex-grow-1")
    titleBox.appendChild(thirdRow)
    let playtime = setPlaytime(idPrefix, game);
    thirdRow.appendChild(playtime)
    let publisher = setPublisher(idPrefix, game);
    thirdRow.appendChild(publisher)
    let listBox = document.createElement("div");
    listBox.classList.add("col-md-12", "col-lg-7", "d-flex", "flex-column");
    gameEle.appendChild(listBox)
    let peopleRow = document.createElement("div");
    peopleRow.classList.add("row");
    listBox.appendChild(peopleRow);
    let designers = setListElement(["col", "cellBorderBot", "cellBorderRight", "cellBorderLeft"], "Designers", idPrefix,
                                "designer", "designers", game)
    peopleRow.appendChild(designers);
    let artists = setListElement(["col", "cellBorderBot"], "Artists", idPrefix,
        "artist", "artists", game)
    peopleRow.appendChild(artists);
    let rulesRow = document.createElement("div");
    rulesRow.classList.add("row", "flex-grow-1");
    listBox.appendChild(rulesRow);
    let categories = setListElement(["col", "cellBorderBot", "cellBorderRight", "cellBorderLeft", "overflow-y-hidden"],
        "Categories", idPrefix, "category", "categories", game)
    rulesRow.appendChild(categories);
    let mechanics = setListElement(["col", "cellBorderBot", "overflow-y-hidden"],
        "Mechanics", idPrefix, "mechanic", "mechanics", game)
    rulesRow.appendChild(mechanics);

}

function setListElement(classes, title, prefix, noun, nounPlural, game){
    let col = document.createElement("div");
    classes.forEach((cls) => {
        col.classList.add(cls);
    })
    col.innerHTML = "<div><b>" + title + ":</b></div>";
    let list = document.createElement("span");
    list.id = prefix + nounPlural;
    list.innerHTML = "";
    col.appendChild(list)
    if(game != null){
        game[nounPlural].forEach((obj, i, arr) =>{
            let ele = document.createElement("span");
            ele.id = prefix + noun + i;
            ele.innerHTML = obj
            if(i < arr.length-1){
                ele.innerHTML += ", "
            }
            list.appendChild(ele);
        })
    }
    else {
        let prog = document.createElement("div")
        prog.classList.add("progress")
        list.appendChild(prog);
        let progbar = document.createElement("div")
        progbar.id = nounPlural+"bar";
        progbar.classList.add("progress-bar")
        progbar.style.width = "0%";
        progbar.innerHTML = "0%"
        prog.appendChild(progbar)
    }
    return col
}

function setPublisher(prefix, game){
    let publisherCol = document.createElement("div");
    publisherCol.classList.add("col", "cellBorderBot");
    publisherCol.innerHTML = "Published By: ";
    let publisher = document.createElement("span");
    publisher.id = prefix + "publisher";
    publisher.innerHTML = "?????????????????";
    if(game != null){
        publisher.innerHTML = game.publisher;
    }
    publisherCol.appendChild(publisher);
    return publisherCol;
}

function setMinAge(prefix, game){
    let age = document.createElement("div");
    age.classList.add("col", "cellBorderBot");
    age.innerHTML = "Age: ";
    let minAge = document.createElement("span");
    minAge.id = prefix + "minage";
    minAge.innerHTML = "?";
    if(game != null){
        minAge.innerHTML = game.minage;
    }
    age.appendChild(minAge);
    return age;
}

function setPlaytime(prefix, game){
    let playtime = document.createElement("div");
    playtime.classList.add("col", "cellBorderBot", "cellBorderRight");
    let minPlaytime = document.createElement("span");
    minPlaytime.id = prefix + "minplaytime";
    minPlaytime.innerHTML = "??";
    if(game != null){
        minPlaytime.innerHTML = game.minplaytime;
    }
    playtime.appendChild(minPlaytime);
    playtime.innerHTML += " - ";
    let maxPlaytime = document.createElement("span");
    maxPlaytime.id = prefix + "maxplaytime";
    maxPlaytime.innerHTML = "???";
    if(game != null){
        maxPlaytime.innerHTML = game.maxplaytime;
    }
    playtime.appendChild(maxPlaytime);
    playtime.innerHTML += " Min.";

    return playtime
}

function setPlayers(prefix, game){
    let players = document.createElement("div");
    players.classList.add("col", "cellBorderBot", "cellBorderRight");
    let minPlayers = document.createElement("span");
    minPlayers.id = prefix + "minplayers";
    minPlayers.innerHTML = "?";
    if(game != null){
        minPlayers.innerHTML = game.minplayers;
    }
    players.appendChild(minPlayers);
    players.innerHTML += " - ";
    let maxPlayers = document.createElement("span");
    maxPlayers.id = prefix + "maxplayers";
    maxPlayers.innerHTML = "??";
    if(game != null){
        maxPlayers.innerHTML = game.maxplayers;
    }
    players.appendChild(maxPlayers);
    players.innerHTML += " Players";

    return players
}

function setTitle(prefix, game){
    let titleH = document.createElement("h4")
    let name = document.createElement("span");
    name.id = prefix + "name";
    name.innerHTML = "?";
    if(game != null){
        name.innerHTML = game.name;
    }
    titleH.appendChild(name);
    titleH.innerHTML += " ";
    let year = document.createElement("span");
    year.id = prefix + "year";
    year.innerHTML = "(????)";
    if(game != null){
        year.innerHTML = "(" + game.year + ")";
    }
    titleH.appendChild(year)

    return titleH;
}

function setImage(id, classes, game){
    let imgDiv = document.createElement("div");
    classes.forEach((cls) => {
        imgDiv.classList.add(cls);
    })
    let img = document.createElement("img");
    img.id = id;
    img.classList.add("img-fluid", "rounded");
    img.src = "img/placeholder.png";
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
function toggleShare(){
    let shareScreen = document.getElementById("sharePopup");
    if(shareScreen.classList.contains("d-none")){
        shareScreen.classList.remove("d-none");
        shareScreen.addEventListener("keyup", (e) => {
            if(e.key === "Escape"){
                toggleShare();
            }
        })
    } else {
        shareScreen.removeEventListener("keyup", (e) => {
            if(e.key === "Escape"){
                toggleShare();
            }
        })
        shareScreen.classList.add("d-none");
    }
}

let spoilerIsChecked = false;

function toggleSpoiler(){
    let spoilerCheck = document.getElementById("spoilerCheck");
    if(spoilerIsChecked){
        spoilerCheck.classList.add("btnCheckOff");
        spoilerCheck.classList.remove("btnCheckOn");
        spoilerIsChecked = false;
    } else {
        spoilerCheck.classList.remove("btnCheckOff");
        spoilerCheck.classList.add("btnCheckOn");
        spoilerIsChecked = true;
    }
}
function createSpoilerForClipboard(e){
    let clipBtn = e.currentTarget;
    let text = "BGdle: Guessed the game in " + guessList.length + " guesses on " + dailyDate + " (" + hints + " hints used). Try your luck on https://www.bgdle.com"
    if(spoilerIsChecked) {
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
    }
    navigator.clipboard.writeText(text);
    if(!clipBtn.classList.contains("greenBG")) {
        clipBtn.classList.add("greenBG");
        setTimeout(function () {
            clipBtn.classList.remove("greenBG");
        }, 3000);
    }
}

function capitalizeFirstLetter(string){
    let firstLetter = string.charAt(0).toUpperCase();
    return firstLetter + string.slice(1)
}

const Comparison = {
    A_LOT_LOWER: -2,
    LOWER: -1,
    SAME: 0,
    HIGHER: 1,
    A_LOT_HIGHER: 2
}


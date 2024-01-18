<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="google-site-verification" content="WY2i1IxYctQVSUS0M06mLYxBYn5gU712W3eAenWSm28" />
    <title>BGDLE</title>
    <link rel="icon" href="img/placeholder.png">
    <link href="css/bs/bootstrap.css" rel="stylesheet">
    <script src="js/bs/bootstrap.min.js" ></script>
    <link rel="stylesheet" href="icon/font/bootstrap-icons.min.css">
    <script src="js/jquery-3.7.1.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;1000&display=swap" rel="stylesheet">
    <link href="css/style.css?67" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?<?php echo filemtime('css/style.css') ?>"/>
    <script src="js/bgdle.js?<?php echo filemtime('js/bgdle.js') ?>" defer></script>
</head>
<body onload="setupPage()">
<div id="winScreen" class="container position-absolute frontPage z-3 rounded g-1 d-none">
    <div class="row">
        <div class="col">
            <div class="row">
                <div class="col higher">
                    <span id="winDate">2024-01-01</span>
                </div>
                <div class="col-2"></div>
            </div>
            <div class="row">
                <div class="col correct">
                    You guessed the game in <span id="winGuessCounter"></span> guesses! <span id="winHints"></span>
                </div>
            </div>
            <div class="row">
                <div class="col lower">
                    See you tomorrow for the next game.
                </div>
            </div>
            <div class="row border-top border-bottom" id="winLogin">
                <div class="col-8">
                    Login to record stats.
                </div>
                <div class="col">
                    <button class="btn btn-help winSmallerFont" type="button" id="winLoginButton">Login</button>
                </div>
            </div>
            <div class="row border-top border-bottom winSmallerFont d-none" id="winLoggedIn">
                <div class="col ">
                    Total Wins: <span id="winTotal"></span>
                    Streak: <span id="winStreak"></span>
                </div>
                <div class="col ">
                    Average: <span id="winAvgGuesses"></span><span id="winAvgHints"></span>
                </div>
            </div>
        </div>
        <div class="col-2 border-start">
            <div class="row-cols-1 winSmallerFont">
                <div class="col p-1">
                    <button class="btn btn-help" type="button" id="retryButton" title="Retry daily from the start"><i class="bi-arrow-clockwise"></i></button>
                </div>
                <div class="col p-1">
                    <button class="btn btn-help" type="button" id="clipboardButton" title="Copy stats to clipboard"><i class="bi-clipboard-fill" id="clipboardIcon"></i></button>
                </div>
                <div class="col p-1">

                </div>
                <div class="col p-1">
                    <button class="btn btn-help" type="button" id="spoilerButton" title="Copy guesses to clipboard"><i class="bi-list"></i></button>
                </div>
                <div class="col p-1">
                    <button class="btn btn-help" type="button" id="spoilerDiscordButton" title="Copy guesses to clipboard for Discord"><i class="bi-discord"></i></button>
                </div>
            </div>
        </div>
    </div>

</div>
<div id="welcomeScreen" class="container position-absolute frontPage z-3 rounded g-1" >
    <div class="row">
        <div class="col"></div>
        <div class="col-3">
            <button type="button" class="btn btn-help" id="playDailyBtn">Play Daily</button>
            <button type="button" class="btn btn-help" id="helpCloseBtn">X</button>
        </div>
    </div>
    <div class="row">
        <div class="col h4 fw-bold">
            <span class="h3 higher fw-bold">B</span>oard <span class="h3 higher fw-bold">G</span>ame wor<span class="h3 fw-bold">DLE</span> <span class="h6">inspired game</span>
        </div>
    </div>
    <div class="row">
        <div class="col">
            Welcome to a little game I made. Every day I will think about one game from the BGG Top 1000 games,
            and you can try to guess it. When you guess a board game that shares specific similarities,
            I will point them out. Your goal is to get the game in the fewest guesses. <b>Good Luck!</b>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <p>Rules:</p>
            <ul>
                <li>You can guess a game by entering the name of a game and selecting it in the dropdown.</li>
                <li>Correct properties will be colored <span class="correct"><i class="bi-check"></i> green</span>
                    (and checkmark added) on the guess. They will also be noted above the searchbar.</li>
                <li>Year released, Min-max players, min-max playtime (on the box values) and
                    min. age will be compared to the answer. It will be colored based if the answer is
                    <span class="higher"><i class="bi-chevron-up"></i> higher,</span>
                    or <span class="lower"><i class="bi-chevron-down"></i> lower</span> with respective icons</li>
                <li>Publisher is the first one listed on BGG (based on my assumption,
                    it is the first publisher to release the game</li>
                <li>Designers, Artists, Categories and Mechanics are lists.
                    When you correctly guess a few of them, the answer (above the search bar) will show you
                    how many you guess correctly out of 100%.</li>
                <li>Once properties are guessed you can click on <span class="correct">them</span>
                    to filter your search bar. Clicking it again, disables your filter.
                    You can only have one filter at a time.</li>
                <li>Using filters increases your <span class="lower">Hints used counter</span> (near the guess counter).
                    Once a filter is set, only the first search will add to the hints counter.
                    Changing filters back and forth will increase the hints counter each time.</li>
                <li>Every 10 guesses the first unknown letter will be relieved from the title.</li>
            </ul>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <b>Notes from me:</b> The game feels a bit too hard for me, and I'm looking into what can I give as hints that could be given automatically (without me having to manually pick them). I added a few that might be helpful.
            Any ideas or issues? Please make an issue on <a href="https://github.com/Algraud/bgdle/issues" target="_blank">Github</a> or <a href = "https://discord.gg/HtPdy3WsVk" target="_blank">Discord.</a>
        </div>
        </div>
</div>
<div id="loginScreen" class="container position-absolute frontPage z-3 rounded g-1 d-none">
    <div class="row">
        <div class="col" id="loginTitle">Login</div>
    </div>
    <div class="row">
        <div class="col higher" id="loginError"></div>
    </div>
    <div class="row">
        <div class="col">
            <label for="usernameField" class="form-label">Username:</label>
            <input type="text" id="usernameField" class="form-control">
        </div>
    </div>
    <div class="row">
        <div class="col">
            <label for="passwordField" class="form-label">Password:</label>
            <input type="password" id="passwordField" class="form-control">
        </div>
    </div>
    <div class="row d-none" id="emailRow">
        <div class="col">
            <label for="emailField" class="form-label">Email(Optional):</label>
            <input type="email" id="emailField" class="form-control">
        </div>
    </div>
    <div class="row top-buffer">
        <div class="col">
            <button type="button" class="btn btn-help" id="changeLoginBtn">Sign-Up instead</button>
        </div>
        <div class="col">
            <button type="button" class="btn btn-help" id="submitLoginBtn">Login</button>
        </div>
    </div>
</div>

<div id="legend">
    <div id="legendTab">
        <div>
            <p>Legend</p>
        </div>
    </div>
    <div>
        <div>
            <ul id="legendBody" class="list-group list-group-flush">
                <li class="list-group-item"><span class="correct"><i class="bi-check"></i>Same as Answer</span></li>
                <li class="list-group-item"><span class="higher"><i class="bi-chevron-up"></i>Answer is higher</span></li>
                <li class="list-group-item"><span class="lower"><i class="bi-chevron-down"></i>Answer is lower</span></li>
            </ul>
        </div>
    </div>
</div>
<div class="container text-center">
    <div class="row" id="titleRow">
        <div class="col-4" id="dateCol">
            <div class="row">
                <div class="col">
                    <span id="title">Daily: 2024-01-03</span>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <span id="timeLeft">Time Left: 2024-01-03</span>
                </div>
            </div>
        </div>
        <div class="col" id="titleCol">
            <span id="titleLetters">BG</span>dle
        </div>
        <div class="col-4" id="helpCol">
            <span id="usernameTitle" class="correct"></span>
            <button type="button" class="btn btn-help btn-title" id="loginTitleBtn"><i class="bi-box-arrow-in-right titleBtnIcon" id="loginIcon"></i></button>
            <button type="button" class="btn btn-help btn-title" id="helpBtn"><i class="bi-question-lg titleBtnIcon" id="helpIcon"></i></button>
        </div>
    </div>
    <div class="row top-buffer">
        <div class="col" id="answerCol">
            <div class="row" id="answer1" style="display: none">
                <div class="col-3" id="answer1img">
                    <img src="img/placeholder.png" class="img-fluid rounded" alt="game Name">
                </div>
                <div class="col d-flex flex-column">
                    <div class="row">
                        <h4>Game Name
                            <span id="answer1year" class="text-warning">(<i class="bi-chevron-double-down"></i>2020)</span></h4>
                    </div>
                    <div class="row">
                        <div class="col border-top border-end">
                            <span class="text-success" id="answer1minPlayers">
                                <i class="bi-check"></i>1
                            </span> -
                            <span class="text-info" id="answer1maxPlayers">
                                <i class="bi-chevron-up"></i>4
                            </span> Players
                        </div>
                        <div class="col border-top border-end">
                            <div class="row">
                                <div class="col">
                                    <span class="text-success" id="answer1minPlaytime">
                                        <i class="bi-check"></i>30
                                    </span> -
                                    <span class="text-info" id="answer1maxPlaytime">
                                        <i class="bi-chevron-up"></i>120
                                    </span> Min.
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">Playtime</div>
                            </div>
                        </div>
                        <div class="col border-top border-end">
                            <div class="row">
                                <div class="col">
                                    Age:
                                    <span class="text-success" id="answer1minAge">
                                        <i class="bi-check"></i>12
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col border-top ">
                            <div class="row">
                                <div class="col">
                                    Published by:
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                            <span class="text-success" id="answer1publisher">
                                <i class="bi-check"></i>Stonemeiyer Games Inc.
                            </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col border-top border-end">
                            <div><b>Designers:</b></div>
                            <span id="answer1designers">
                        <span id="answer1designer1">Tom Bradley, </span>
                        <span id="answer1designer2">John McLenon</span>
                    </span>
                        </div>
                        <div class="col border-top">
                            <div><b>Artists:</b></div>
                            <span id="answer1artists">
                        <span id="answer1artists1">Tom Bradley, </span>
                        <span id="answer1artists2">John McLenon</span>
                    </span>
                        </div>
                    </div>
                    <div class="row flex-grow-1">
                        <div class="col border-top border-end overflow-y-hidden">
                            <div><b>Categories:</b></div>
                            <span id="answer1categories">
                        <span id="answer1category1">Tom Bradley, </span>
                        <span id="answer1category2">John McLenon</span>
                    </span>
                        </div>
                        <div class="col border-top overflow-y-hidden">
                            <div><b>Mechanics:</b></div>
                            <span id="answer1mechanics">
                        <span id="answer1mechanic1">Tom Bradley, </span>
                        <span id="answer1mechanic2">John McLenon</span>
                    </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="row top-buffer" id="searchBarRow">
        <div class="col " id="searchBarCol">
            <div class="container position-relative">
                <div class="row" id="searchBar">
                    <input id="searchInput" class="form-control form-control-lg" type="text" placeholder="Enter board game name here...">
                </div>
                <div class="z-2 position-absolute row w-100">
                    <div id="searchResults" class="col d-none">

                    </div>
                </div>
            </div>
        </div>
        <div class="col-3 h3 rounded" id="guessCounterCol">
            <div class="row">
                <div class="col-9">
                    Guess #<span id="guessCounter">0</span>
                </div>
                <div class="col-3">
                    <span id="hintCounter" title="Hints used"></span>
                </div>
            </div>
        </div>
    </div>
    <div class="row top-buffer" id="guessesRow">
        <div class="col" id="guessesCol">
            <div class="row bg-gradient rounded top-buffer d-none" id="guess1">
                <div class="col-3" id="guess1img">
                    <img src="https://cf.geekdo-images.com/oXUkkh9uq3zBVWQ8mbgMfQ__imagepage/img/DaWOlDtxDRns4ibNr1_NYlicTw8=/fit-in/900x600/filters:no_upscale():strip_icc()/pic7947338.png" class="img-fluid rounded" alt="Wyrmspan">
                </div>
                <div class="col d-flex flex-column">
                    <div class="row">
                        <h4>Game Name
                        <span id="guess1year" class="text-warning">(<i class="bi-chevron-double-down"></i>2020)</span></h4>
                    </div>
                    <div class="row">
                        <div class="col border-top border-end">
                            <span class="text-success" id="guess1minPlayers">
                                <i class="bi-check"></i>1
                            </span> -
                            <span class="text-info" id="guess1maxPlayers">
                                <i class="bi-chevron-up"></i>4
                            </span> Players
                        </div>
                        <div class="col border-top border-end">
                            <div class="row">
                                <div class="col">
                                    <span class="text-success" id="guess1minPlaytime">
                                        <i class="bi-check"></i>30
                                    </span> -
                                        <span class="text-info" id="guess1maxPlaytime">
                                        <i class="bi-chevron-up"></i>120
                                    </span> Min.
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">Playtime</div>
                            </div>
                        </div>
                        <div class="col border-top border-end">
                            <div class="row">
                                <div class="col">
                                    Age:
                                    <span class="text-success" id="guess1minAge">
                                        <i class="bi-check"></i>12
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col border-top ">
                            <div class="row">
                                <div class="col">
                                    Published by:
                                </div>
                            </div>
                            <div class="row">
                                <div class="col">
                                    <span class="text-success" id="guess1publisher">
                                        <i class="bi-check"></i>Stonemeiyer Games Inc.
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col border-top border-end">
                            <div><b>Designers:</b></div>
                            <span id="guess1designers">
                                <span id="guess1designer1">Tom Bradley, </span>
                                <span id="guess1designer2">John McLenon</span>
                            </span>
                        </div>
                        <div class="col border-top">
                            <div><b>Artists:</b></div>
                            <span id="guess1artists">
                                <span id="guess1artists1">Tom Bradley, </span>
                                <span id="guess1artists2">John McLenon</span>
                            </span>
                        </div>
                    </div>
                    <div class="row flex-grow-1">
                        <div class="col border-top border-end overflow-y-hidden">
                            <div><b>Categories:</b></div>
                            <span id="guess1categories">
                                <span id="guess1category1">Tom Bradley, </span>
                                <span id="guess1category2">John McLenon</span>
                            </span>
                        </div>
                        <div class="col border-top overflow-y-hidden">
                            <div><b>Mechanics:</b></div>
                            <span id="guess1mechanics">
                                <span id="guess1mechanic1">Tom Bradley, </span>
                                <span id="guess1mechanic2">John McLenon</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

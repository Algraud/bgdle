<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, interactive-widget=resizes-content"">
    <meta name="google-site-verification" content="WY2i1IxYctQVSUS0M06mLYxBYn5gU712W3eAenWSm28" />
    <title>BGDOKU</title>
    <link rel="icon" href="img/placeholder.png">
    <link href="css/bs/bootstrap.css" rel="stylesheet">
    <script src="js/bs/bootstrap.bundle.js" ></script>
    <link rel="stylesheet" href="icon/font/bootstrap-icons.min.css">
    <script src="js/jquery-3.7.1.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?<?php echo filemtime('css/style.css') ?>"/>
    <script src="js/bgdoku.js?<?php echo filemtime('js/bgdoku.js') ?>" defer></script>
</head>
<body onload="setupBGdoku()">
<div id="curtain" class="position-absolute">
    <div id="curtainTitle">
        <div><span id="titleLetters" class="biggerTitle">BG</span>doku</div>
    </div>
    <div class="lds-ring"><div></div><div></div><div></div><div></div></div>
</div>
<div class="modal fade" id="winBackdrop" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title" id="winBackdropLabel">Out of Guesses...</h1>
                    <button type="button" class="btn-close" id="winCloseButton"></button>
                </div>
                <div class="modal-body">
                    <div class="container fw-bold">
                        <div class="row">
                            <div class="col higher d-flex justify-content-center h3 fw-bold">
                                <span id="winDate">2024-01-01</span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col correct">
                                You correctly guessed <span id="winGuessCounter"></span> out of 9 spots!
                            </div>
                        </div>
                        <div class="row">
                            <div class="col lower">
                                See you tomorrow for the next game.
                            </div>
                        </div>
                        <div class="row">
                            <div class="col compareOthers" id="winTotalDiv">
                                A total of <span id="winTotal">-1</span> people have finished a game today!
                            </div>
                        </div>
                        <!--
                        <div class="row fw-normal" id="winLogin">
                            <div class="col-8">
                                Login to record stats.
                            </div>
                        </div>
                        -->
                        <div class="row border-top winSmallerFont fw-normal d-none" id="winLoggedIn">
                            <div class="col ">
                                Total Wins: <span id="winTotal"></span>
                                Streak: <span id="winStreak"></span>
                            </div>
                            <div class="col ">
                                Average: <span id="winAvgGuesses"></span><span id="winAvgHints"></span>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <div class="row winSmallerFont">
                        <div class="col">
                            <!--
                            <button class="btn btn-help" type="button" id="winLoginButton" data-bs-dismiss="modal">Login</button>
                            -->
                            <button class="btn btn-help" type="button" id="retryButton" title="Retry daily from the start">Retry</i></button>
                            <div class="btn-group" role="group">
                                <button class="btn btn-help fakeBtn" type="button" disabled>Share:</button>
                                <button class="btn btn-help btnCheckOff d-none" type="button" id="spoilerCheck">Spoiler Off</button>
                                <button class="btn btn-help" type="button" id="spoilerButton" title="Copy guesses to clipboard"><i class="bi-copy"></i></button>
                                <button class="btn btn-help d-none" type="button" id="spoilerDiscordButton" title="Copy guesses to clipboard for Discord"><i class="bi-discord"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="welcomeBackdrop">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-title h4 fw-bold">
                        <span class="h3 higher fw-bold">B</span>oard <span class="h3 higher fw-bold">G</span>ame <span class="h3 fw-bold">DOKU</span> <span class="h6">inspired game</span>
                    </div>
                    <button type="button" class="btn-close" id="welcomeCloseButton"></button>
                </div>
                <div class="modal-body">
                    <div class="container">
                        <div class="row">
                            <div class="col">
                                <b>Welcome</b> to the second little game I made.<br> Every day BGDoku will generate a 3x3 grid of different categories.
                                Your job is to guess a game for each intersecting category so that it would represent both categories.
                                You have ten guesses and your goal is to get all 9 correctly.<br> <b>Good Luck!</b>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row">
                        <div class="col">
                            <button type="button" class="btn btn-help d-none" id="howToPlayBtn" data-bs-target="#tutorial1" data-bs-toggle="modal" disabled>How To Play?</button>
                            <button type="button" class="btn btn-help" id="faqBtn" data-bs-target="#faq" data-bs-toggle="modal">FAQ</button>
                            <button type="button" class="btn btn-help" id="bgdleBtn">Go to BGdle</button>
                            <button type="button" class="btn btn-help" id="playDailyBtn">Play Daily</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="faq">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-title h4 fw-bold">
                        <span class="h3 higher fw-bold">B</span>oard <span class="h3 higher fw-bold">G</span>ame <span class="h3 fw-bold">DOKU</span> <span class="h6">inspired game</span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="container">
                        <div class="row">
                            <div class="col">
                                <div class="accordion" id="faqAccordion">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingHowToPlay">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqHowToPlay" aria-expanded="false" aria-controls="collapseHowToPlay">
                                                How do I play?
                                            </button>
                                        </h2>
                                        <div id="faqHowToPlay" class="accordion-collapse collapse" aria-labelledby="headingHowToPlay" data-bs-parent="#faqAccordion">
                                            <div class="accordion-body">
                                                You need to click the question marks for the search screen to appear. There you can search boardgames by their name. You need to find one that matches both categories. You have 10 tries for all spaces combined. Good Luck!
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingPercent">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqPercent" aria-expanded="false" aria-controls="collapsePercent">
                                                When I check other's answers, there are a few games that are 0%. What does that mean?
                                            </button>
                                        </h2>
                                        <div id="faqPercent" class="accordion-collapse collapse" aria-labelledby="headingPercent" data-bs-parent="#faqAccordion">
                                            <div class="accordion-body">
                                                The percentages show what percentage of people guessed this specific game for this slot. However, at the start of a day, when checking if categories are possible by finding a few games that have those categories. These games are saved, just in case people want to see examples after the game.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingCantFindBG">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCantFindBG" aria-expanded="false" aria-controls="collapseCantFindBG">
                                                I can't find this specific game when searching!
                                            </button>
                                        </h2>
                                        <div id="faqCantFindBG" class="accordion-collapse collapse" aria-labelledby="headingCantFindBG" data-bs-parent="#faqAccordion">
                                            <div class="accordion-body">
                                                Unfortunately, the database does not have all the games on BGG. We do make sure to have the top 1000 game on BGG daily. There is also a process which adds games below top 1000, but it needs manual updates and are not that frequent.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingGameIsWrong">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqGameIsWrong" aria-expanded="false" aria-controls="collapseGameIsWrong">
                                                It says my pick is wrong, but I know its right!
                                            </button>
                                        </h2>
                                        <div id="faqGameIsWrong" class="accordion-collapse collapse" aria-labelledby="headingGameIsWrong" data-bs-parent="#faqAccordion">
                                            <div class="accordion-body">
                                                There are two possible explanations here. Either BGG doesn't mark it correctly, or my data is old. First check with BGG and if their data is incorrect, offer a correction. If BGG is correct and my data is wrong, you can let me know.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingAnIdea">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqAnIdea" aria-expanded="false" aria-controls="collapseAnIdea">
                                                I have a cool idea for category (or another type of idea)!
                                            </button>
                                        </h2>
                                        <div id="faqAnIdea" class="accordion-collapse collapse" aria-labelledby="headingAnIdea" data-bs-parent="#faqAccordion">
                                            <div class="accordion-body">
                                                You can add suggestions in the discord and I will try to implement them. If it is a category, it needs to not be too niche. The system randomly picks categories and then checks if the database has 5 games that work for each square.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingContact">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqContact" aria-expanded="false" aria-controls="collapseContact">
                                                How can I contact you?
                                            </button>
                                        </h2>
                                        <div id="faqContact" class="accordion-collapse collapse" aria-labelledby="headingContact" data-bs-parent="#faqAccordion">
                                            <div class="accordion-body">
                                                I have a bgg account and gmail account by the same name (algraud). But the fastest one would be through the <a href = "https://discord.gg/HtPdy3WsVk" target="_blank">Discord server for BGdle&BGdoku</a>.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingSupport">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqSupport" aria-expanded="false" aria-controls="collapseSupport">
                                                How can I support you?
                                            </button>
                                        </h2>
                                        <div id="faqSupport" class="accordion-collapse collapse" aria-labelledby="headingSupport" data-bs-parent="#faqAccordion">
                                            <div class="accordion-body">
                                                By playing the game and telling others about it. If you  want to support in another way, you can use the Kofi to leave me a tip.
                                                <iframe id='kofiframe' src='https://ko-fi.com/algraud/?hidefeed=true&widget=true&embed=true&preview=true' style='border:none;width:100%;padding:4px;background:#f9f9f9;' height='712' title='algraud'></iframe>
                                                <script src='https://storage.ko-fi.com/cdn/scripts/overlay-widget.js'></script>
                                                <script>
                                                    kofiWidgetOverlay.draw('algraud', {
                                                        'type': 'floating-chat',
                                                        'floating-chat.donateButton.text': 'Tip Me',
                                                        'floating-chat.donateButton.background-color': '#5cb85c',
                                                        'floating-chat.donateButton.text-color': '#fff'
                                                    });
                                                </script>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row">
                        <div class="col">
                            <button class="btn btn-help" data-bs-target="#welcomeBackdrop" data-bs-toggle="modal">Home</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="searchModal">
    <div class="modal-dialog-scrollable modal-lg modal-dialog-centered modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div class="container">
                    <div class="row">
                        <h1 class="modal-title fs-5" id="searchTitle">Enter a Board Game</h1>
                    </div>
                    <div class="row">
                        <h5 class="modal-title underTitle" id="SearchTitleDescriptionTop">Sample help</h5>
                    </div>
                    <div class="row">
                        <h5 class="modal-title underTitle" id="SearchTitleDescriptionSide">Sample help2</h5>
                    </div>
                    <div class="row" id="searchBar">
                        <input id="searchInput" class="form-control form-control-lg" type="text" placeholder="Enter board game name here...">
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="container">
                    <div class="row w-100">
                        <div id="searchResultsDoku" class="col d-none">

                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
<div class="container text-center">
    <div class="row" id="titleRow">
        <div class="col-md-4 col-sm-12" id="dateCol">
            <div class="row">
                <div class="col ">
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
            <div><span id="titleLetters">BG</span>doku</div>
        </div>
        <div class="col-md-4 col-sm-12" id="helpCol">
            <span id="usernameTitle" class="correct"></span>
            <div class="btn-group">
                <button type="button" class="btn btn-help btn-title disabled" id="loginTitleBtn">Login</i></button>
                <button type="button" class="btn btn-help btn-title d-none" id="winBtn">Results</button>
                <button type="button" class="btn btn-help btn-title" id="helpBtn">Help</i></button>
            </div>
        </div>
    </div>
    <div id="infoRow" class="row">
        <div class="col"></div>
        <div id="counterCol" class="col-6 col-md-3 strong-underline">
                <span >Guesses Left: </span>
                <span id="dokuCounter">10</span>
        </div>
        <div id="resultsToggle" class="col d-none top-buffer">
            <div class="btn-group">
                <input type="radio" class="btn-check" id="playerResultsToggle" name="resultsToggleBtn" autocomplete="off" checked>
                <label class="btn btnToggle" for="playerResultsToggle">My Results</label>
                <input type="radio" class="btn-check" id="allResultsToggle" name="resultsToggleBtn" autocomplete="off">
                <label class="btn btnToggle" for="allResultsToggle">Everyone's Results</label>
            </div>
        </div>
        <div class="col"></div>
    </div>
    <div id="board" class="row">
        <div class="col">
            <div id="dokuRow0" class="headRow dokuRow row">
                <div id="doku00" class="col-2 dokuBorderRight dokuBorderBottom"></div>
                <div id="doku01" class="col dokuHead dokuBorderRight">
                    <span>Sample Text</span>
                </div>
                <div id="doku02" class="col dokuHead dokuBorderRight">
                    <span>Sample Text Sample Text Sample Text Sample Text</span>
                </div>
                <div id="doku03" class="col dokuHead ">
                    <span>Sample Text</span>
                </div>
            </div>
            <div id="dokuRow1" class="dokuRow row">
                <div id="doku10" class="col-2 dokuSide dokuBorderBottom">
                    <span>Sample Text</span>
                </div>
                <div id="doku11" class="col dokuPanel">
                    <span class="placeholderQuestionMark">?</span>
                </div>
                <div id="doku12" class="col dokuPanel">
                    <span class="placeholderQuestionMark">?</span>
                </div>
                <div id="doku13" class="col dokuPanel">
                    <span class="placeholderQuestionMark">?</span>
                </div>
            </div>
            <div id="dokuRow2" class="dokuRow row">
                <div id="doku20" class="col-2 dokuSide dokuBorderBottom">
                    <span>Sample Text</span>
                </div>
                <div id="doku21" class="col dokuPanel">
                    <span class="placeholderQuestionMark">?</span>
                </div>
                <div id="doku22" class="col dokuPanel">
                    <span class="placeholderQuestionMark">?</span>
                </div>
                <div id="doku23" class="col dokuPanel">
                    <span class="placeholderQuestionMark">?</span>
                </div>
            </div>
            <div id="dokuRow3" class="dokuRow row">
                <div id="doku30" class="col-2 dokuSide ">
                    <span>Sample Text Sample Text Sample Text Sample Text</span>
                </div>
                <div id="doku31" class="col dokuPanel">
                    <span class="placeholderQuestionMark">?</span>
                </div>
                <div id="doku32" class="col dokuPanel">
                    <span class="placeholderQuestionMark">?</span>
                </div>
                <div id="doku33" class="col dokuPanel">
                    <span class="placeholderQuestionMark">?</span>
                </div>
            </div>
        </div>
    </div>
    <div id="boardStats" class="row d-none">
        <div class="col">
            <div id="dokuStatsRow0" class="headRow dokuRow row">
                <div id="dokuStats00" class="col-2 dokuBorderRight dokuBorderBottom"></div>
                <div id="dokuStats01" class="col dokuHead dokuBorderRight">
                    <span>Sample Text</span>
                </div>
                <div id="dokuStats02" class="col dokuHead dokuBorderRight">
                    <span>Sample Text Sample Text Sample Text Sample Text</span>
                </div>
                <div id="dokuStats03" class="col dokuHead ">
                    <span>Sample Text</span>
                </div>
            </div>
            <div id="dokuStatsRow1" class="dokuRow row">
                <div id="dokuStats10" class="col-2 dokuSide dokuBorderBottom">
                    <span>Sample Text</span>
                </div>
                <div id="dokuStats11" class="col dokuPanel">
                    <span class="placeholderQuestionMark">?</span>
                </div>
                <div id="dokuStats12" class="col dokuPanel">
                    <span class="placeholderQuestionMark">?</span>
                </div>
                <div id="dokuStats13" class="col dokuPanel">
                    <span class="placeholderQuestionMark">?</span>
                </div>
            </div>
            <div id="dokuStatsRow2" class="dokuRow row">
                <div id="dokuStats20" class="col-2 dokuSide dokuBorderBottom">
                    <span>Sample Text</span>
                </div>
                <div id="dokuStats21" class="col dokuPanel">
                    <span class="placeholderQuestionMark">?</span>
                </div>
                <div id="dokuStats22" class="col dokuPanel">
                    <span class="placeholderQuestionMark">?</span>
                </div>
                <div id="dokuStats23" class="col dokuPanel">
                    <span class="placeholderQuestionMark">?</span>
                </div>
            </div>
            <div id="dokuStatsRow3" class="dokuRow row">
                <div id="dokuStats30" class="col-2 dokuSide ">
                    <span>Sample Text Sample Text Sample Text Sample Text</span>
                </div>
                <div id="dokuStats31" class="col dokuPanel">
                    <span class="placeholderQuestionMark">?</span>
                </div>
                <div id="dokuStats32" class="col dokuPanel">
                    <span class="placeholderQuestionMark">?</span>
                </div>
                <div id="dokuStats33" class="col dokuPanel">
                    <span class="placeholderQuestionMark">?</span>
                </div>
            </div>
        </div>
    </div>
    <div class="row top-buffer">
        <div class="col">
            <button type="button" class="btn btn-help btn-title" id="giveUpBtn">Give Up</button>
        </div>
    </div>
    <div class="row top-buffer" id="BggRow">
        <a href="https://boardgamegeek.com/"><img id="bggLogo" src="img/BGG.png"></a>
    </div>
</div>
</body>
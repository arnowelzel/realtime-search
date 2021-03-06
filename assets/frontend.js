var realTimeSearchTimer = null;
var realTimeSearchWaiting = false;
var realTimeSearchText = '';
var realTimeSearchActive = false;

function realTimeSearchSearchAjax() {
    let searchInput = document.getElementById('realTimeSearchSearchInput');
    let text = searchInput.value;
    if (realTimeSearchText == text) {
        return;
    }
    if (text == '') {
        document.getElementById('realTimeSearchResult').style.display = 'none';
        realTimeSearchActive = false;
        window.clearInterval(realTimeSearchTimer);
    } else {
        if (!realTimeSearchWaiting) {
            realTimeSearchText = text;
            realTimeSearchWaiting = true;
            jQuery.ajax({
                type: 'POST',
                url: realtimesearch_options.ajaxurl,
                data: {
                    action: 'realTimeSearch_search',
                    text: text
                },
                success: function (data, textStatus, XMLHttpRequest) {
                    realTimeSearchTimer = null;
                    let searchInput = document.getElementById('realTimeSearchSearchInput');
                    let searchResult = document.getElementById('realTimeSearchResult');
                    let bounds = searchInput.getBoundingClientRect();
                    searchResult.innerHTML = data;
                    let left = (bounds.left - 250);
                    if (left < 0) {
                        left = 0;
                    }
                    searchResult.style.left = left + 'px';
                    searchResult.style.top = (bounds.top + bounds.height + window.scrollY) + 'px';
                    searchResult.style.width = (bounds.width + 250) + 'px';
                    searchResult.style.display = 'block';
                    let listEntry = searchResult.firstElementChild.firstElementChild;
                    while (listEntry) {
                        listEntry.firstElementChild.addEventListener('keydown', event => {
                            let entry = event.target.parentElement;
                            const realTimeSearchResult = document.getElementById('realTimeSearchResult');
                            if (event.keyCode == 40 && realTimeSearchActive) {
                                if (entry.nextElementSibling) {
                                    entry.nextElementSibling.firstElementChild.focus();
                                } else {
                                    document.getElementById('realTimeSearchSearchInput').focus();
                                }
                                event.preventDefault();
                            } else if (event.keyCode == 38 && realTimeSearchActive) {
                                if (entry.previousElementSibling) {
                                    entry.previousElementSibling.firstElementChild.focus();
                                } else {
                                    document.getElementById('realTimeSearchSearchInput').focus();
                                }
                                event.preventDefault();
                            } else if (event.keyCode == 27) {
                                realTimeSearchResult.style.display = 'none';
                                event.preventDefault();
                            }
                        });
                        listEntry = listEntry.nextElementSibling;
                    }
                    realTimeSearchWaiting = false;
                    realTimeSearchActive = true;
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    realTimeSearchWaiting = false;
                    console.log('realTimeSearchSearch error: ' + errorThrown)
                }
            });
        }
    }
}

function realTimeSearchSearch() {
    if (realTimeSearchTimer != null) {
        window.clearInterval(realTimeSearchTimer);
    }

    realTimeSearchTimer = window.setInterval('realTimeSearchSearchAjax()', 250);
}

jQuery(document).ready(function () {
    document.getElementById('realTimeSearchSearchInput').setAttribute('autocomplete', 'off');

    document.addEventListener('click', (evt) => {
        const realTimeSearchSearchInput = document.getElementById('realTimeSearchSearchInput');
        const realTimeSearchResult = document.getElementById('realTimeSearchResult');
        let targetElement = evt.target;

        do {
            if (targetElement == realTimeSearchResult || targetElement == realTimeSearchSearchInput) {
                return;
            }
            targetElement = targetElement.parentNode;
        } while (targetElement);

        realTimeSearchResult.style.display = 'none';
    });

    document.getElementById('realTimeSearchSearchInput').addEventListener('focus', (event) => {
        if (realTimeSearchActive) {
            document.getElementById('realTimeSearchResult').style.display = 'block';
        }
    });

    document.getElementById('realTimeSearchSearchInput').addEventListener('keydown', (event) => {
        const realTimeSearchResult = document.getElementById('realTimeSearchResult');
        if (event.keyCode == 40 && realTimeSearchActive && !realTimeSearchWaiting) {
            realTimeSearchResult.style.display = 'block';
            realTimeSearchResult.firstElementChild.firstElementChild.firstElementChild.focus();
            event.preventDefault();
        } else if (event.keyCode == 38 && realTimeSearchActive && !realTimeSearchWaiting) {
            realTimeSearchResult.style.display = 'block';
            realTimeSearchResult.firstElementChild.lrealTimeSearchElementChild.firstElementChild.focus();
            event.preventDefault();
        } else if (event.keyCode == 27 && realTimeSearchActive && !realTimeSearchWaiting) {
            realTimeSearchResult.style.display = 'none';
            event.preventDefault();
        } else {
            realTimeSearchSearch();
        }
    });
});

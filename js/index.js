async function load() {
    $(document).on("click", ".series-show", async (event) => {
        NProgress.start();
        let data = await (await fetch("https://cors-anywhere.herokuapp.com/https://apps-api.uitzendinggemist.nl/series/" + $(event.target).closest(".series-item").data("id") + ".json")).json();
        $(".series-image").attr("src", data["image"]);
        $(".series-title").text(data["name"]);
        $(".series-description").text(await npo.translate("NL", data["description"]));
        $(".series-broadcasters").text(data["broadcasters"].join(", "));
        $(".series-episodes-count").text(data["active_episodes_count"]);
        let episodeContent = "";
        for(let i = 0; i < data["episodes"].length; i++) {
            let episode = data["episodes"][i];
            if(!episode.revoked) {
                episodeContent += `<a class="list-group-item d-flex justify-content-between align-items-center" href="player.html?v=` + episode["mid"] + `">
                                        <img src="` + episode["stills"][0]["url"] + `" alt="Still" class="modal-episode-image float-left ">
                                        <span class="w-75 ml-2">` + episode["name"] + `<br>
                                            <small class="">` + new Date(episode["broadcasted_at"] * 1000).toLocaleDateString() + `</small>
                                        </span>
                                        <span class="badge badge-secondary badge-pill">` + new Date(episode["duration"] * 1000).toISOString().substr(11, 8) + `</span>
                                    </a>`
            }
        }
        $(".series-episodes").html(episodeContent);
        $(".series-modal").modal("show");
        NProgress.done();
    });

    $(".filter").change((event) => {
        filters[$(event.target).data("prop")] = $(event.target).val() === "null" ? null : $(event.target).val();
        showSeries();
    })
}

let filters = {
    az: null,
    genreId: null,
    dateFrom: null,
    pageSize: 24,
    page: 1
};

function removeEmpty(filters) {
    let obj = {};
    for(let key in filters) {
        if(!filters.hasOwnProperty(key)) continue;
        if(filters[key] != null) obj[key] = filters[key];
    }
    return obj;
}

async function showSeries(firstRun) {
    NProgress.start();
    let catalogue = await (await fetch("https://cors-anywhere.herokuapp.com/https://start-api.npo.nl/page/catalogue?" + $.param(removeEmpty(filters)), {
        headers: {
            "ApiKey": "e45fe473feaf42ad9a215007c6aa5e7e"
        }
    })).json();

    if(firstRun) {
        let filterAz = catalogue["components"][0]["filters"][0]["options"];
        let filterAzContent = "";
        filterAz.forEach(f => {
            filterAzContent += `<option value="` + f.value + `">` + f.display + `</option>`
        });
        $("#filterAz").html(filterAzContent);

        let filterGenre = catalogue["components"][0]["filters"][1]["options"];
        let filterGenreContent = "";
        filterGenre.forEach(f => {
            filterGenreContent += `<option value="` + f.value + `">` + f.display + `</option>`
        });
        $("#filterGenre").html(filterGenreContent);

        let filterMostWatched = catalogue["components"][0]["filters"][2]["options"];
        let filterMostWatchedContent = "";
        filterMostWatched.forEach(f => {
            filterMostWatchedContent += `<option value="` + f.value + `">` + f.display + `</option>`
        });
        $("#filterMostWatched").html(filterMostWatchedContent);
    }

    let amountOfItems = catalogue["components"][1]["data"]["total"];
    let pages = Math.ceil(amountOfItems / filters.pageSize);
    let filterPageContent = "";
    for(let i = 1; i <= pages; i++) {
        filterPageContent += `<option value="` + i + `">` + i + `</option>`
    }
    $("#filterPage").html(filterPageContent);

    let series = catalogue["components"][1]["data"]["items"];
    let content = "";

    for(let i = 0; i < series.length; i++) {
        let serie = series[i];

        let image = "";
        if (serie.images != null && serie.images.header != null && serie.images.header.formats != null && serie.images.header.formats.web != null) {
            image = serie.images.header.formats.web.source;
        } else if (serie.images != null && serie.images.original != null && serie.images.original.formats != null && serie.images.original.formats.web != null) {
            image = serie.images.original.formats.web.source;
        }

        content += `
            <div class="col-md-2 series-item" data-id="` + serie.id + `">
                    <div class="card mb-4 box-shadow series-show">
                        <img class="card-img-top overview-image" src="` + image + `" alt="Series image" onerror="this.src='https://placehold.it/400x200'">
                        <div class="card-body">
                            <h5 class="card-title overflow">` + serie.title + `</h5>
                            <p class="card-text"></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted overflow">` + serie.broadcasters.join(", ") + `</small>
                            </div>
                        </div>
                    </div>
                </div>`
    }

    $("#series").html(content);
    NProgress.done();
}

load();
showSeries(true);
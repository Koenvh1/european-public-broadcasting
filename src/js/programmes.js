class Programmes {
    constructor() {
        this.filters = {
            az: null,
            genreId: null,
            dateFrom: null,
            pageSize: 24,
            page: 1
        };

        this.translations = {
            genres: {
                null: "All genres",
                documentaire: "Documentary",
                film: "Film",
                humor: "Humor",
                jeugd: "Youth",
                muziek: "Music",
                natuur: "Nature",
                reizen: "Travel",
                serie: "Series"
            },
        };
    }

    async load() {
        $(document).on("click", ".series-show", async (event) => {
            this.showSeriesModal($(event.target).closest(".series-item").data("id"));
        });

        $(".filter").change((event) => {
            this.filters[$(event.target).data("prop")] = $(event.target).val() === "null" ? null : $(event.target).val();
            this.showSeriesCollection();
        });

        $(".close-search-results").click(event => {
            $(".close-search-results").hide();
            $("#search").val("");
            this.showSeriesCollection(true);
        });

        $(".series-seasons").change(event => {
            let parts = $(".series-seasons").val().split("|");
            this.switchSeason(parts[0], parts[1]);
        });
    }

    removeEmpty(filters) {
        let obj = {};
        for (let key in filters) {
            if (!filters.hasOwnProperty(key)) continue;
            if (filters[key] != null) obj[key] = filters[key];
        }
        return obj;
    }

    async showSeriesModal(id) {
        NProgress.start();
        let data = await
            npo.getJson("https://start-api.npo.nl/page/franchise/" + id + "?page=10000");
        let serie = data["components"][0]["series"];
        let image = "";
        if (serie.images != null && serie.images.header != null && serie.images.header.formats != null && serie.images.header.formats.web != null) {
            image = serie.images.header.formats.web.source;
        } else if (serie.images != null && serie.images.original != null && serie.images.original.formats != null && serie.images.original.formats.web != null) {
            image = serie.images.original.formats.web.source;
        }

        $(".series-image").attr("src", image);
        $(".series-title").text(serie["title"]);
        $(".series-description").html(await
            npo.translate("EN", serie["description"])
        )
        ;
        $(".series-broadcasters").text(serie["broadcasters"].join(", "));
        $(".series-episodes-count").text(data["components"][2]["data"]["total"]);
        $(".series-channel").attr("src", "img/" + serie["channel"] + ".svg");
        let seasonContent = "";
        if (data["components"][2]["filter"] != null) {
            for (let i = 0; i < data["components"][2]["filter"]["options"].length; i++) {
                let f = data["components"][2]["filter"]["options"][i];
                seasonContent += `<option value="` + id + "|" + f.value + `">` + f.display + `</option>`
            }
            $(".series-seasons").html(seasonContent).show();
        } else {
            $(".series-seasons").hide();
        }
        this.renderEpisodes(data["components"][2]["data"]);
        $(".series-modal").modal("show");
        NProgress.done();
    }

    renderEpisodes(data) {
        let items = data["items"];
        let episodeContent = "";
        for (let i = 0; i < items.length; i++) {
            let episode = items[i];
            let image = "";
            if (episode.images != null && episode.images.header != null && episode.images.header.formats != null && episode.images.header.formats.web != null) {
                image = episode.images.header.formats.web.source;
            } else if (episode.images != null && episode.images.original != null && episode.images.original.formats != null && episode.images.original.formats.web != null) {
                image = episode.images.original.formats.web.source;
            }
            episodeContent += `<a class="list-group-item d-flex justify-content-between align-items-center" href="player.html?v=` + episode["id"] + `">
                                        <img src="` + image + `" alt="Still" class="modal-episode-image float-left ">
                                        <span class="w-75 ml-2">` + (episode["episodeTitle"] == null ? episode["title"] : episode["episodeTitle"]) + `<br>
                                            <small class="">Episode ` + episode["episodeNumber"] + ` - ` + new Date(Date.parse(episode["broadcastDate"])).toLocaleDateString() + `</small>
                                        </span>
                                        <span class="badge badge-secondary badge-pill">` + new Date(episode["duration"] * 1000).toISOString().substr(11, 8) + `</span>
                                    </a>`
        }

        if (data["_links"].hasOwnProperty("prev")) {
            $(".series-previous").show().off("click").click(async event => {
                await this.switchSeasonUrl(data["_links"]["prev"]["href"]);
            });
        } else {
            $(".series-previous").hide();
        }
        if (data["_links"].hasOwnProperty("next")) {
            $(".series-next").show().off("click").click(async event => {
                await this.switchSeasonUrl(data["_links"]["next"]["href"]);
            });
        } else {
            $(".series-next").hide();
        }

        $(".series-episodes").html(episodeContent);
    }

    async showSeriesCollection(firstRun) {
        NProgress.start();
        let catalogue = await
            npo.getJson("https://start-api.npo.nl/page/catalogue?" + $.param(this.removeEmpty(this.filters)));

        if (firstRun) {
            let filterAz = catalogue["components"][0]["filters"][0]["options"];
            let filterAzContent = "";
            filterAz.forEach(f => {
                filterAzContent += `<option value="` + f.value + `">` + f.display + `</option>`
            });
            $("#filterAz").html(filterAzContent);

            let filterGenre = catalogue["components"][0]["filters"][1]["options"];
            let filterGenreContent = "";
            filterGenre.forEach(f => {
                filterGenreContent += `<option value="` + f.value + `">` + this.translations.genres[f.value] + `</option>`
            });
            $("#filterGenre").html(filterGenreContent);

            let filterMostWatched = catalogue["components"][0]["filters"][2]["options"];
            let filterMostWatchedContent = "";
            filterMostWatched.forEach(f => {
                filterMostWatchedContent += `<option value="` + f.value + `">` + (f.value === "2014-01-01" ? "All time" : f.value) + `</option>`
            });
            $("#filterMostWatched").html(filterMostWatchedContent);
        }

        let amountOfItems = catalogue["components"][1]["data"]["total"];
        let pages = Math.ceil(amountOfItems / this.filters.pageSize);
        let filterPageContent = "";
        for (let i = 1; i <= pages; i++) {
            filterPageContent += `<option value="` + i + `" ` + (parseInt(this.filters.page) === i ? "selected" : "") + `>` + i + `</option>`
        }
        $("#filterPage").html(filterPageContent);

        let series = catalogue["components"][1]["data"]["items"];
        this.renderSeries(series);
        NProgress.done();
    }

    async switchSeasonUrl(url) {
        let data = await
            npo.getJson(url);
        this.renderEpisodes(data);
    }

    async switchSeason(id, season) {
        let data = await
            npo.getJson("https://start-api.npo.nl/media/series/" + id + "/episodes?seasonId=" + season);
        this.renderEpisodes(data);
    }

    async search(term) {
        let data = await
            npo.getJson("https://start-api.npo.nl/search?pageSize=24&page=1&query=" + encodeURIComponent(term));
        $(".close-search-results").show();
        this.renderSeries(data["items"]);
    }

    renderSeries(series) {
        let content = "";

        for (let i = 0; i < series.length; i++) {
            let serie = series[i];

            let image = "";
            if (serie.images != null && serie.images.header != null && serie.images.header.formats != null && serie.images.header.formats.web != null) {
                image = serie.images.header.formats.web.source;
            } else if (serie.images != null && serie.images.original != null && serie.images.original.formats != null && serie.images.original.formats.web != null) {
                image = serie.images.original.formats.web.source;
            }

            content += `
            <div class="col-lg-2 col-md-3 col-sm-12 series-item" data-id="` + serie.id + `">
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
    }
}
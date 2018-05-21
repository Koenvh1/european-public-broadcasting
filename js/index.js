async function load() {
    let series = await (await fetch("https://cors-anywhere.herokuapp.com/https://apps-api.uitzendinggemist.nl/series.json")).json();

    let content = "";

    for(let i = 0; i < series.length; i++) {
        let serie = series[i];

        content += `
            <div class="col-md-2 series-item" data-id="` + serie.mid + `">
                    <div class="card mb-4 box-shadow series-show">
                        <img class="card-img-top overview-image" src="` + serie.image + `" alt="Series image" onerror="this.src='https://placehold.it/400x200'">
                        <div class="card-body">
                            <h5 class="card-title overflow">` + serie.name + `</h5>
                            <p class="card-text"></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted overflow">` + serie.broadcasters.join(", ") + `</small>
                            </div>
                        </div>
                    </div>
                </div>`
    }

    $("#series").html(content);

    $(".series-show").on("click", async (event) => {
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
    })
}

load();
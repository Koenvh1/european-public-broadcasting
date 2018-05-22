const videoPlayer = document.querySelector("#videoPlayer");
const languages = [
    {
        code: "DE",
        name: "Deutsch"
    },
    {
        code: "EN",
        name: "English"
    },
    {
        code: "ES",
        name: "Español"
    },
    {
        code: "FR",
        name: "français"
    },
    {
        code: "IT",
        name: "Italiano"
    },
    {
        code: "PL",
        name: "Polski"
    },
];
let translated = {
    "DE": [],
    "EN": [],
    "ES": [],
    "FR": [],
    "IT": [],
    "PL": []
};

async function getVideoUrl(videoId) {
    let tokenResponse = await fetch("https://ida.omroep.nl/app.php/auth");
    let token = (await tokenResponse.json())["token"];

    let linkResponse = await fetch("https://ida.omroep.nl/app.php/" + videoId + "?adaptive=yes&token=" + token);
    let items = (await linkResponse.json())["items"][0];
    let url = items.find(item => item["label"] === "Hoog")["url"];
    url = url.replace("callback=?", "callback=videoInfo");

    let script = document.createElement("script");
    script.src = url;
    document.body.appendChild(script);

    let subtitleResponse = await fetch("https://rs.poms.omroep.nl/v1/api/subtitles/" + videoId + "/nl_NL/CAPTION.vtt");
    let blob = new Blob([(await subtitleResponse.text())]);
    let blobUrl = URL.createObjectURL(blob);

    languages.forEach(language => {
        let track = document.createElement("track");
        track.kind = "subtitles";
        track.label = language.name;
        track.srclang = language.code;
        track.src = blobUrl;

        videoPlayer.appendChild(track);
    });
}

async function videoInfo(callback) {
    if(callback.errorstring) {
        alert(await npo.translate("EN", callback.errorstring))
    }
    videoPlayer.src = callback.url;
}

async function translateSubtitles(track, start) {
    let cues = track.cues;

        console.log(track.cues[start].text);
    let newItems = [];
    for (let i = start - 1; i < start + 3; i++) {
        // Get the next five that have NOT been translated yet.
        if (translated[track.language].includes(i)) continue;
        newItems.push(i);
    }

    for (let i = start - 1; i < start + 3; i++) {
        // Only translate the next five that have not been translated.
        if (!newItems.includes(i)) continue;
        const cue = cues[i];

        if (!cue.text) continue; // Cue text can be empty on mistake (when still processing), skip it then.
        translated[track.language].push(i); // Mark this as translated.

        let text = cue.text;
        //console.log(cue.text);
        cue.text = "";
        cue.text = await npo.translate(track.language, text);

    }
}

videoPlayer.addEventListener("loadedmetadata", () => {
    const index = languages.findIndex(l => l.code === "EN");
    videoPlayer.textTracks[index].mode = "showing";
});

function getCurrentTrack() {
    let track = null;
    for (let i = 0; i < videoPlayer.textTracks.length; i++) {
        if (videoPlayer.textTracks[i].mode === "showing") {
            track = videoPlayer.textTracks[i];
            break;
        }
    }
    return track;
}

async function load() {
    NProgress.start();
    let videoId = new URL(window.location).searchParams.get("v");
    await getVideoUrl(videoId);
    let episode = await npo.getJson("https://start-api.npo.nl/page/episode/" + videoId);
    episode = episode["components"][0]["episode"];
    $(".series-title").text(episode["title"]);
    $(".series-broadcasters").text(episode["broadcasters"].join(", "));
    $(".series-date").text(`season ` + episode["seasonNumber"] + ` episode ` + episode["episodeNumber"] + ` - ` + new Date(Date.parse(episode["broadcastDate"])).toLocaleDateString());
    $(".series-description").html(await npo.translate("EN", episode["description"]));
    $(".series-channel").attr("src", "img/" + episode["channel"] + ".svg");

    setInterval(async () => {
        let track = getCurrentTrack();
        if (track != null && track.activeCues != null && track.activeCues.length > 0) {
            for(let i = 0; i < track.activeCues.length; i++) {
                let id = parseInt(track.activeCues[i]["id"]);
                if (!isNaN(id)) {
                    await translateSubtitles(track, id);
                }
            }
        }
    }, 100);

    setInterval(async () => {
        if (!videoPlayer.paused && $("#enableOcr").is(":checked")) {
            await ocr();
        }
    }, 2000);
    NProgress.done();
}

load();

let lastOcr = null;

async function ocr() {
    let canvas = document.createElement("canvas");
    let w = videoPlayer.videoWidth;
    let h = Math.round(videoPlayer.videoHeight / 4);
    canvas.width = w;
    canvas.height = h;

    let context = canvas.getContext("2d");
    context.drawImage(videoPlayer, 0, h * 3, w, h, 0, 0, w, h);
    let image = canvas.toDataURL("image/png");

    $("#ocr").attr("src", image);

    image = image.split(",")[1];
    let response = await npo.ocr(image);
    if(response != null && lastOcr !== response) {
        lastOcr = response;
        const track = getCurrentTrack();
        let translation = await npo.translate(track.language, response);
        //console.log(translation);

        /*
        for(let i = 0; i < videoPlayer.textTracks[1].activeCues.length; i++) {
            let cue = videoPlayer.textTracks[1].activeCues[i];
            if(cue.text === translation) {
                cue.endTime += 2;
                return;
            }
        }
        */
        track.addCue(new VTTCue(videoPlayer.currentTime, videoPlayer.currentTime + 1.8, translation));

    }
}
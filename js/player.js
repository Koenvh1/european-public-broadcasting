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
    videoPlayer.src = callback.url;
}

async function translateSubtitles(track, start) {
    let cues = track.cues;

    let newItems = [];
    for (let i = start - 1; i < start + 8; i++) {
        // Get the next five that have NOT been translated yet.
        if (translated[track.language].includes(i)) continue;
        newItems.push(i);
    }

    for (let i = start - 1; i < start + 8; i++) {
        // Only translate the next five that have not been translated.
        if (!newItems.includes(i)) continue;
        const cue = cues[i];

        if (!cue.text) continue; // Cue text can be empty on mistake (when still processing), skip it then.
        translated[track.language].push(i); // Mark this as translated.

        let text = cue.text;
        console.log(cue.text);
        cue.text = "";
        cue.text = await npo.translate(track.language, text);

    }
}

videoPlayer.addEventListener("loadedmetadata", () => {
    videoPlayer.textTracks[languages.findIndex(l => l.code === "EN")].mode = "showing";
});

async function load() {
    NProgress.start();
    let videoId = new URL(window.location).searchParams.get("v");
    await getVideoUrl(videoId);
    let episode = await (await fetch("https://cors-anywhere.herokuapp.com/https://apps-api.uitzendinggemist.nl/episodes/" + videoId + ".json")).json();
    $(".series-title").text(episode["name"]);
    $(".series-broadcasters").text(episode["broadcasters"].join(", "));
    $(".series-date").text(new Date(episode["broadcasted_at"] * 1000).toLocaleDateString());
    $(".series-description").text(await npo.translate("NL", episode["description"]));

    setInterval(async () => {
        let track = null;
        for (let i = 0; i < videoPlayer.textTracks.length; i++) {
            if (videoPlayer.textTracks[i].mode === "showing") {
                track = videoPlayer.textTracks[i];
                break;
            }
        }
        if (track != null && track.activeCues != null && track.activeCues.length > 0) {
            await translateSubtitles(track, parseInt(track.activeCues[0]["id"]));
        }
    }, 100);
    NProgress.done();
}

load();
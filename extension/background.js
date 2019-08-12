function matchRuleShort(str, rule) {
    return new RegExp("^" + rule.split("*").join(".*") + "$").test(str);
}

browser.browserAction.onClicked.addListener(async () => {
    let url = await (browser.tabs.query({currentWindow: true, active: true}));
    url = url[0].url;
    console.log(url);

    const ard = matchRuleShort(url, "*://*ardmediathek.de/*");
    const ceskatelevize = matchRuleShort(url, "*://*ceskatelevize.cz/porady/*");
    const dr = matchRuleShort(url, "*://*dr.dk/tv/*");
    const err = matchRuleShort(url, "*://etv2.err.ee/*") || matchRuleShort(url, "*://etv.err.ee/v/elusaated/*");
    const nrk = matchRuleShort(url, "*://tv.nrk.no/*");
    const rtbf = matchRuleShort(url, "*://*rtbf.be/auvio/*");
    const rts = matchRuleShort(url, "*://*rts.ch/play/*");
    const svt = matchRuleShort(url, "*://*svtplay.se/*");
    const tvp = matchRuleShort(url, "*://vod.tvp.pl/*");
    const yle = matchRuleShort(url, "*://areena.yle.fi/*");
    const zdf = matchRuleShort(url, "*://zdf.de/*");

    let broadcaster = "";
    if (ard) {
        broadcaster = "ard";
    } else if (ceskatelevize) {
        broadcaster = "ceskatelevize";
    } else if (dr) {
        broadcaster = "dr";
    } else if (err) {
        broadcaster = "err";
    } else if (nrk) {
        broadcaster = "nrk";
    } else if (rtbf) {
        broadcaster = "rtbf";
    } else if (rts) {
        broadcaster = "rts";
    } else if (svt) {
        broadcaster = "svt";
    } else if (tvp) {
        broadcaster = "tvp";
    } else if (yle) {
        broadcaster = "yle";
    } else if (zdf) {
        broadcaster = "zdf";
    } else {
        const alertWindow = `alert("This URL is not supported (yet).")`;
        browser.tabs.executeScript({code : alertWindow});
        return;
    }

    browser.tabs.update({
        url: `https://npo.koenvh.nl/player-standalone.html?broadcaster=${broadcaster}&videoUrl=${encodeURIComponent(url)}`
    });
});
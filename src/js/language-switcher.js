(() => {
    const langs = Utils.getLanguages();

    const languagesContent = Object.entries(langs).map(lang =>
        `<button class="dropdown-item language-button" type="button" data-lang="${lang[0]}">${lang[1]}</button>`
    ).join("\n");

    $(".languages").html(languagesContent);

    $(".language-button").click(e => {
        localStorage.setItem("language", $(e.target).data("lang"));

        alert("This only affects automatically translated texts at the moment");
        location.reload();
    })
})();
document.addEventListener("DOMContentLoaded", () => {
    const addButton = document.querySelector(".showOverlay");
    const overlay = document.querySelector(".add-deck-overlay");
    const cancelButton = document.getElementById("cancel");
    const form = document.getElementById("overlay-form");

    addButton.addEventListener("click", () => {
        overlay.classList.remove("hide-overlay");
        document.body.classList.add("no-scroll");
    });

    cancelButton.addEventListener("click", (e) => {
        e.preventDefault();
        overlay.classList.add("hide-overlay");
        document.body.classList.remove("no-scroll");
        form.reset();
    });

    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) {
        overlay.classList.add("hide-overlay");
        document.body.classList.remove("no-scroll");
        form.reset();
      }
  });
});

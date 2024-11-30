const menu = document.querySelectorAll(".menu");
const sidebarLabels = document.querySelectorAll(".aside-labels");
const sidebar = document.querySelector(".sidebar");
const overlay = document.querySelector(".overlay")


function mobile() {
  sidebar.classList.toggle("visible");
  overlay.classList.toggle("mobile-overlay");
  document.body.classList.toggle("no-scroll");

  sidebarLabels.forEach(element => {
    element.classList.toggle("aside-labels-hidden")
  });
}

menu[0].addEventListener("click", mobile);
menu[1].addEventListener("click", mobile);
overlay.addEventListener("click", mobile);
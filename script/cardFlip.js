const answers = document.querySelector('.answers');
const card = document.querySelector('.card-holder');

card.addEventListener("click", () => {
  answers.classList.toggle("hide")
  card.classList.toggle("card-click")
});
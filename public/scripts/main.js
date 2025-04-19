const menuIcon = document.querySelector("#hamburger-menu");
const menu = document.querySelector(".desktop-icons");

menuIcon.addEventListener("click", () => {
    menu.classList.toggle("show-element");
});
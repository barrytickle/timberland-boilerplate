const switchToCloseIcon = (label) => {
  console.log("LabeL", label);
  label.querySelector("svg.open").classList.add("hidden");
  label.querySelector("svg.close").classList.remove("hidden");
};

const switchToOpenIcon = (label) => {
  label.querySelector("svg.open").classList.remove("hidden");
  label.querySelector("svg.close").classList.add("hidden");
};

const toggleHamburger = (e) => {
  const target = e.target.matches("label")
    ? e.target
    : e.target.closest("label");

  target.classList.toggle("active");

  if (target.classList.contains("active")) {
    document.body.classList.add("no-move");
    switchToCloseIcon(target);
  } else {
    document.body.classList.remove("no-move");
    switchToOpenIcon(target);
  }
};

const bindHamburgerIcon = () => {
  const hamburgerIcon = document.querySelector(
    ".js-header .js-hamburger-toggle"
  );
  if (!!!hamburgerIcon) {
    console.log("Unable to find hamburger icon");
    return;
  }

  console.log("Hamburger icon found", hamburgerIcon);

  hamburgerIcon.addEventListener("click", toggleHamburger);
};

(() => {
  bindHamburgerIcon();
})();

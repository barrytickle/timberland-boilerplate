/**
 * Block previews render real links, so clicking a button or card in the editor
 * would navigate the editor away. Cancel the navigation but let the click carry
 * on, so it still selects the block and ACF inline editing keeps working.
 */
document.addEventListener(
  "click",
  (event) => {
    if (event.target.closest('[data-type^="acf/"] a[href]')) {
      event.preventDefault();
    }
  },
  true
);

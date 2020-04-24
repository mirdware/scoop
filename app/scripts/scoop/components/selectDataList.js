export default ($) => ({
  mutate: (e) => {
    const { target } = e;
    const selects = document.querySelectorAll('select[list="'+target.id+'"]');
    for (let i = 0, select; select = selects[i]; i++) {
      select.innerHTML = target.innerHTML;
    }
  }
});

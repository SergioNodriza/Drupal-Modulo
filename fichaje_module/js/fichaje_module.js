function showHideFilters() {
  const x = document.getElementById("hideShow");

  if (x.style.display === "none") {
    x.style.display = "block";
  } else {
    x.style.display = "none";
  }
}

function showHide(key) {

  const x = document.getElementById(key);

    if (x.style.visibility === "collapse") {
      x.style.visibility = "visible";
    } else {
      x.style.visibility = "collapse";
    }
}

function clearValue(key)
{
  const x = document.getElementById(key);
  x.value = '';
}

function showHide() {
  const x = document.getElementById("hideShow");

  if (x.style.display === "none") {
    x.style.display = "block";
  } else {
    x.style.display = "none";
  }
}

function showHideTables(key) {

  const x = document.getElementById(key);

    if (x.style.visibility === "collapse") {
      x.style.visibility = "visible";
    } else {
      x.style.visibility = "collapse";
    }
}

function reset () {
  window.location.replace("/parte");
}

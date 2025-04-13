function showForm(formId) {
  document.getElementById("mainContainer").style.display = "none";
  document.querySelectorAll('.form-wrapper').forEach(form => {
    form.style.display = "none";
  });
  document.getElementById(formId).style.display = "block";
}

function goBack() {
  document.getElementById("mainContainer").style.display = "flex";
  document.querySelectorAll('.form-wrapper').forEach(form => {
    form.style.display = "none";
  });
}

document.getElementById("avatar_file").onchange = function() {
  document.getElementById("avatar_form").submit();
}

// ----
// CODE BLOCK
// ----
const inputs = document.getElementById('profile').querySelectorAll("input, select, textarea");
const formGroups = document.getElementById('profile').querySelectorAll(".form-group");
const footers = document.getElementById('profile').querySelectorAll(".card-footer");
inputs.forEach(input => input.disabled = true);
formGroups.forEach(group => group.classList.add('view'));
footers.forEach(footer => footer.style.display = 'none');

window.editProfile = function () {
  inputs.forEach(input => input.disabled = !input.disabled)
  formGroups.forEach(group => {
    if(group.classList.contains('view')) {
      group.classList.remove('view')
      footers.forEach(footer => footer.style.display = 'block');
    } else {
      group.classList.add('view')
      footers.forEach(footer => footer.style.display = 'none');
    }
  })
}

Rapyd.Core.GoogleMap.formBuilder(
  "address_street",   // ele_street,
  "address_city",     // ele_city,
  "address_state",    // ele_state,
  "address_zip",      // ele_zip,
  "address_street_2", // ele_street2,
  "address_county",   // ele_county
  null,               // ele_label
  null                // map_canvas
);

// Format Credit Card
const input_credit_card = document.getElementById('billing_card_number');
Rapyd.Core.Formatters.restrictCreditCard(input_credit_card);

// Format Epiration Date
const input_exp_date = document.getElementById('billing_exp');
Rapyd.Core.Formatters.restrictExpDate(input_exp_date);

window.get_bank_name = function () {
  var routing_num = document.getElementById(`routing_number`).value;

  fetch('https://www.routingnumbers.info/api/data.json?rn='+routing_num)
    .then(response => response.json())
    .then(data => {
      document.getElementById(`bank_name`).value = data.customer_name;
    });
}

// ----
// TOUR GUIDE
// ----

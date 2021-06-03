((_self, _props) => {
  Rapyd.Core.UsergroupCompletion = {
    props: {
    },
    init() {
      _self = this;
      _props = _self.props;

      document.querySelectorAll(".usergroup_form")
        .forEach(form =>
          form.addEventListener("submit", _self.spaNavigate)
        );

      document.querySelector('#spa-arrow-prev').addEventListener('click', _self.spaNavigate)
      document.querySelector('#tax_id_type').addEventListener('change', _self.formatTxId)
    },
    formatTxId(event) {
      const type = event.target.value;
      const tax_id = document.getElementById("tax_id");
      tax_id.disabled = false;

      if(type === 'SSN') {
        Rapyd.Core.Formatters.restrictSSN(tax_id);
      } else if(type === 'EIN') {
        Rapyd.Core.Formatters.restrictEIN(tax_id, '-');
      }
    },
    spaNavigate(event) {
      event.preventDefault();
      const next = event.target.dataset.page;
      const inputs = event.target.querySelectorAll("input");
      const selects = event.target.querySelectorAll("select");

      inputs.forEach(input => {
        if(input.value) {
          _props[input.name] = input.value
        }
      });

      selects.forEach(input => {
        if(input.value) {
          _props[input.name] = input.value
        }
      });

      if(next === 'step-3') {
        const tax_id = document.getElementById('tax_id');
        const value = tax_id.value.replace(/\D/g, '').trim();
        if(value.length !== 9) {
          tax_id.classList.add('form-control-warning');
        } else {
          tax_id.classList.remove('form-control-warning');
          Rapyd.Core.SpaSystem.spaHistory(event.target);
        }
      } else if (next === "complete") {
        _self.submitUserGroup(event.target);
      } else {
        Rapyd.Core.SpaSystem.spaHistory(event.target);
      }
    },
    submitUserGroup(target) {
      _props.phone_main = _props.phone_main.replace(/\D/g, "");
      _props.tax_id     = _props.tax_id.replace(/\D/g, "");
      target.dataset.page = 'step-5';
      fetch("/api/ajaxview/gettoken")
        .then(response => response.text())
        .then(token_data => {
          let myHeaders = new Headers();
          myHeaders.append("Content-Type", "application/json");
          myHeaders.append("X-CSRF-TOKEN", token_data);

          let raw = JSON.stringify(_props);

          let requestOptions = {
            method: "POST",
            headers: myHeaders,
            body: raw,
            redirect: "follow"
          };

          fetch("/api/usergroup/complete", requestOptions)
            .then(res => res.json())
            .then(data => window.location = data.redirect);
        });
    }
  };
})();
Rapyd.Core.UsergroupCompletion.init();

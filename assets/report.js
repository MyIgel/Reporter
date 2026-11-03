let modalOpen = false;

const reporter = (e) => {
  if (modalOpen) {
    return;
  }
  e.preventDefault();

  const info = JSON.parse(document.querySelector('meta[name="report-data"]').content);
  const csrf = JSON.parse(document.querySelector('meta[name="csrf-token"]').content);
  const elem = e.target;
  const content = elem.dataset.report_info ?? null;

  // TODO: static at bottom

  document.getElementById('report-modal')?.remove();
  document.body.insertAdjacentHTML(
    'beforeend',
    `<div class="modal" tabindex="-1" id="report-modal">
  <form method="post" target="">
    <div class="modal-dialog">
      <div class="modal-content ${document.body.dataset.theme_type === 'light' ? 'bg-white' : 'bg-dark'}">
        <div class="modal-header">
          <h5 class="modal-title">${info.report}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">

          <input type="hidden" name="_token" value="${csrf}">

<div class="mb-3">
<label class="form-label" for="locales">
${info.reason}
<span class="text-info" title="Pflichtfeld">*</span>
</label>
<select id="locales" name="locales[]"
class="form-control" required multiple>                                                                <option value="de_DE"
selected                >
Deutsch
</option>
<option value="en_US"
selected                >
English
</option>
</select>
</div>



          <div class="mb-3">
            <label for="name" class="form-label ">${info.reason}</label>
            <select >
            <div class="form-text">${info.rason_info}</div>
          </div>

          <div class="mb-3">
            <label for="name" class="form-label ">${info.reason}</label>
            <input type="text" class="form-control" id="report_reason" name="reason" value="Frickelvent">
            <div class="form-text">${info.rason_info}</div>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="${elem.className}"
                  autofocus
                  title="${elem.title}" data-submit="">
            ${elem.innerHTML}
          </button>
        </div>
      </div>
    </div>
  </form>
</div>
      `
  );

  const modal = document.getElementById('confirmation-modal');
  const bootstrapModal = new bootstrap.Modal(modal);
  modal.addEventListener('hide.bs.modal', () => {
    modalOpen = false;
  });

  const modalSubmitButton = modal.querySelector('[data-submit]');
  modalSubmitButton.addEventListener('click', () => {
    console.log('REPORTING!!');
    bootstrapModal.hide();
  });

  /**
   * After the modal has been shown, focus on the "Submit" button in the modal
   * so that it can be confirmed with "Enter".
   */
  modal.addEventListener('shown.bs.modal', () => {
    modalSubmitButton.focus();
  });

  modalOpen = true;
  bootstrapModal.show();
};

window.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('a[href="#report"], [data-report_info]').forEach((elem) => {
    elem.addEventListener('click', reporter);
  });
});

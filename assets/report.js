class Reporter {
  /**
   * @param {HTMLElement} modal
   * @param {NodeListOf<HTMLElement>} triggers
   */
  constructor(modal, triggers) {
    if (!modal) {
      console.warn('Reporter can\'t be initialized, missing modal');
      return;
    }
    if (!triggers.length) {
      console.warn('Reporter can\'t be initialized, missing triggers');
      return;
    }

    this.modalOpen = false;
    this.modal = modal;

    this.csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    this.form = this.modal.querySelector('form');
    this.messageInput = this.modal.querySelector('textarea[name="message"]');
    this.contentInput = this.modal.querySelector('textarea[name="content"]');
    this.contentUseCheckbox = this.modal.querySelector('input[name="use_content"]');
    this.submitButton = this.modal.querySelector('.modal-footer button');

    if (!this.csrfToken || !this.messageInput || !this.contentInput || !this.contentUseCheckbox || !this.submitButton) {
      console.warn('Reporter: Unable to find all form elements');
    }

    this.bootstrapModal = new bootstrap.Modal(this.modal);
    this.modal.addEventListener('hide.bs.modal', () => {
      this.modalOpen = false;
    });
    this.submitButton.addEventListener('click', e => this.submit(e));

    triggers.forEach((element) => {
      element.addEventListener('click', e => this.onClick(e));
    });
  }

  /**
   * @param {Event} e
   */
  onClick(e) {
    e.preventDefault();
    if (this.modalOpen) {
      return;
    }

    const element = e.target;
    const report_info = element.dataset.report_info ?? '';

    this.cleanupAlerts();

    this.messageInput.value = '';
    this.contentInput.value = report_info;
    this.contentInput.parentElement.classList.remove('d-none');
    this.contentUseCheckbox.parentElement.classList.remove('d-none');
    if (!report_info) {
      this.contentInput.parentElement.classList.add('d-none');
      this.contentUseCheckbox.parentElement.classList.add('d-none');
    }

    this.modalOpen = true;
    this.bootstrapModal.show();
  }

  /**
   * @param {Event} e
   */
  async submit(e) {
    e.preventDefault();
    if (!this.form.reportValidity()) {
      console.debug('Reporter: Form invalid');
      return;
    }

    const data = new FormData(this.form);
    if (!this.contentUseCheckbox.checked) {
      data.set('report_info', '-');
    }

    const response = await fetch(this.form.action, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': this.csrfToken
      },
      body: data
    });

    this.cleanupAlerts();
    if (!response.ok) {
      this.formError(`Err:${response.status}`);
      console.debug('Reporter: Invalid server response');
      return;
    }

    const body = await response.json();
    if (body.success) {
      this.formSuccess(body.message);
      this.bootstrapModal.hide();
      console.debug('Reporter: Successful report');
    } else {
      this.formError(`${body.message}`);
      console.debug('Reporter: Error during report handling');
    }
  }

  cleanupAlerts() {
    document.querySelectorAll(('[data-report-alert]')).forEach(e => e.remove());
  }

  /**
   * @param {string} message
   */
  formError(message) {
    this.submitButton.parentElement.insertAdjacentHTML(
      'afterbegin',
      `<div class="alert alert-danger" data-report-alert="" role="alert">${message}</div>`
    );
  }

  /**
   * @param {string} message
   */
  formSuccess(message) {
    const container = document.querySelector('.container-fluid .container, .container-fluid #content');
    container?.insertAdjacentHTML(
      'afterbegin',
      `<div class="alert alert-success" data-report-alert="" role="alert">${message}</div>`
    );
  }
}

window.addEventListener('DOMContentLoaded', () => {
  new Reporter(
    document.getElementById('report'),
    document.querySelectorAll('a[href="#report"], [data-report_info]')
  );
});

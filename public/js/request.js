class CreateRequest {
  constructor({
    formSelector,
    submitButtonSelector,
    callback,
    promptMessage = "Are you sure you want to create this item?",
  }) {
    this.$form = $(formSelector);
    this.$submitButton = $(submitButtonSelector);
    this.callback = typeof callback === "function" ? callback : () => {};
    this.promptMessage = promptMessage;
    this.originalButtonText = this.$submitButton.html();
    this._initEvents();
  }

  _initEvents = () => {
    this.$form.on("submit", (e) => this._handleSubmit(e));
  };

  _toggleSpinner = (show, text) => {
    if (show) {
      this.$submitButton.prop("disabled", true);
      this.$submitButton.html(
        '<i class="fa-spin fa-spinner fas mr-2" aria-hidden="true"></i>' + text
      );
    } else {
      this.$submitButton.prop("disabled", false);
      this.$submitButton.html(text);
    }
  };

  _handleSubmit = (e) => {
    e.preventDefault();
    Swal.fire({
      title: "Are you sure?",
      text: this.promptMessage,
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Yes, create it!",
      cancelButtonText: "Cancel",
    }).then((result) => {
      if (result.isConfirmed) {
        const url = this.$form.attr("action");

        const formData = this._serializeFormToJson();

        this._toggleSpinner(true, "Submitting...");
        axios
          .post(url, formData)
          .then((response) => {
            const data = response.data;
            if (data.status === "error" || data.http_code !== 200) {
              Swal.fire({
                title: "Error!",
                text:
                  data.message ||
                  "Something went wrong. Please try again later.",
                icon: "error",
              });
              this.callback(data.message || "Unknown error", null);
            } else {
              Swal.fire({
                title: "Success!",
                text: data.message || "Your submission was successful!",
                icon: "success",
              }).then((result) => {
                if (result.isConfirmed) {
                  location.reload();
                }
              });
              this.callback(null, data.data);
            }
          })
          .catch((error) => {
            const errorMsg =
              (error.response &&
                error.response.data &&
                error.response.data.message) ||
              "Something went wrong. Please try again later.";
            Swal.fire({
              title: "Error!",
              text: errorMsg,
              icon: "error",
            });
            this.callback(errorMsg, null);
          })
          .finally(() => {
            this._toggleSpinner(false, this.originalButtonText);
          });
      }
    });
  };

  _serializeFormToJson = () => {
    const formData = {};
    this.$form.serializeArray().forEach((field) => {
      formData[field.name] = field.value;
    });
    return formData;
  };
}

window.CreateRequest = CreateRequest;

class UpdateRequest {
  constructor({
    formSelector,
    updateUrl,
    updateData,
    callback,
    promptMessage = "Are you sure you want to update this item?",
    redirectUrl = null,
  }) {
    this.$form = formSelector ? $(formSelector) : null;
    this.updateUrl =
      this.$form && this.$form.attr("action")
        ? this.$form.attr("action")
        : updateUrl;
    this.updateData = updateData;
    this.callback = typeof callback === "function" ? callback : () => {};
    this.promptMessage = promptMessage;
    this.redirectUrl = redirectUrl;

    this.$submitButton = this.$form ? this.$form.find('[type="submit"]') : null;
    this.originalButtonText = this.$submitButton
      ? this.$submitButton.html()
      : "";
  }

  _toggleSpinner = (show, text) => {
    if (this.$submitButton) {
      if (show) {
        this.$submitButton.prop("disabled", true);
        this.$submitButton.html(
          '<i class="fa-spin fa-spinner fas mr-2" aria-hidden="true"></i>' +
            text
        );
      } else {
        this.$submitButton.prop("disabled", false);
        this.$submitButton.html(text);
      }
    }
  };

  send = () => {
    Swal.fire({
      title: "Are you sure?",
      text: this.promptMessage,
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Yes, update it!",
      cancelButtonText: "Cancel",
    }).then((result) => {
      if (result.isConfirmed) {
        this._toggleSpinner(true, "Updating...");

        const formData =
          this.updateData || (this.$form ? this._serializeFormToJson() : null);

        axios
          .post(this.updateUrl, formData)
          .then((response) => {
            const data = response.data;
            if (data.status === "error" || data.http_code !== 200) {
              Swal.fire({
                title: "Error!",
                text: data.message || "Update failed. Please try again later.",
                icon: "error",
              });
              this.callback(data.message || "Unknown error", null);
            } else {
              Swal.fire({
                title: "Success!",
                text: data.message || "Item updated successfully!",
                icon: "success",
              }).then((result) => {
                if (result.isConfirmed) {
                  if (this.redirectUrl) {
                    window.location.href = this.redirectUrl;
                  } else {
                    location.reload();
                  }
                }
              });
              this.callback(null, data.data);
            }
          })
          .catch((error) => {
            const errorMsg =
              (error.response &&
                error.response.data &&
                error.response.data.message) ||
              "Update failed. Please try again later.";
            Swal.fire({
              title: "Error!",
              text: errorMsg,
              icon: "error",
            });
            this.callback(errorMsg, null);
          })
          .finally(() => {
            this._toggleSpinner(false, this.originalButtonText);
          });
      }
    });
  };

  // Method to convert form data into JSON format
  _serializeFormToJson = () => {
    const formData = {};
    this.$form.serializeArray().forEach((field) => {
      formData[field.name] = field.value;
    });
    return formData;
  };
}

window.UpdateRequest = UpdateRequest;

class DeleteRequest {
  constructor({
    deleteUrl,
    data = null,
    callback,
    promptMessage = "Are you sure you want to delete this item?",
  }) {
    this.deleteUrl = deleteUrl;
    this.data = data;
    this.callback = typeof callback === "function" ? callback : () => {};
    this.promptMessage = promptMessage;
  }
  send = () => {
    Swal.fire({
      title: "Are you sure?",
      text: this.promptMessage,
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Yes, delete it!",
      cancelButtonText: "Cancel",
    }).then((result) => {
      if (result.isConfirmed) {
        axios
          .delete(this.deleteUrl, { data: this.data })
          .then((response) => {
            const data = response.data;
            if (data.status === "error" || data.http_code !== 200) {
              Swal.fire({
                title: "Error!",
                text:
                  data.message || "Deletion failed. Please try again later.",
                icon: "error",
              });
              this.callback(data.message || "Unknown error", null);
            } else {
              // reload the page
              Swal.fire({
                title: "Deleted!",
                text: data.message || "Item deleted successfully!",
                icon: "success",
              }).then((result) => {
                if (result.isConfirmed) {
                  location.reload();
                }
              });
              this.callback(null, data.data);
            }
          })
          .catch((error) => {
            const errorMsg =
              (error.response &&
                error.response.data &&
                error.response.data.message) ||
              "Deletion failed. Please try again later.";
            Swal.fire({
              title: "Error!",
              text: errorMsg,
              icon: "error",
            });
            this.callback(errorMsg, null);
          });
      }
    });
  };
}
window.DeleteRequest = DeleteRequest;

class GetRequest {
  constructor({ getUrl, params = {}, callback, promptMessage = null }) {
    this.getUrl = getUrl;
    this.params = params;
    this.callback = typeof callback === "function" ? callback : () => {};
    this.promptMessage = promptMessage;
  }
  send = () => {
    this._executeGet();
  };
  _executeGet = () => {
    Swal.fire({
      title: "Loading...",
      html: "Please wait while the data is being retrieved.",
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      },
      showConfirmButton: false,
    });
    axios
      .get(this.getUrl, { params: this.params })
      .then((response) => {
        const data = response.data;
        if (data.status === "error" || data.http_code !== 200) {
          Swal.fire({
            title: "Error!",
            text:
              data.message ||
              "Failed to retrieve data. Please try again later.",
            icon: "error",
          });
          this.callback(data.message || "Unknown error", null);
        } else {
          Swal.fire({
            title: "Success!",
            text: data.message || "Data retrieved successfully!",
            icon: "success",
          });
          this.callback(null, data.data);
        }
      })
      .catch((error) => {
        const errorMsg =
          (error.response &&
            error.response.data &&
            error.response.data.message) ||
          "Failed to retrieve data. Please try again later.";
        Swal.fire({
          title: "Error!",
          text: errorMsg,
          icon: "error",
        });
        this.callback(errorMsg, null);
      })
      .finally(() => {
        Swal.close();
      });
  };
}
window.GetRequest = GetRequest;

class GetAllRequest {
  constructor({ getUrl, params = {}, callback }) {
    this.getUrl = getUrl;
    this.params = params;
    this.callback = typeof callback === "function" ? callback : () => {};
  }

  send = () => {
    this._executeGet();
  };

  _executeGet = () => {
    Swal.fire({
      title: "Loading...",
      html: "Fetching user data...",
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
      showConfirmButton: false,
    });

    axios
      .get(this.getUrl, { params: this.params })
      .then((response) => {
        const data = response.data;
        console.log("Response:", data);

        if (data.status !== "success") {
          Swal.fire({
            title: "Error!",
            text: data.message || "Failed to fetch data.",
            icon: "error",
          });
          this.callback(data.message || "Unknown error", null);
        } else {
          Swal.close();
          this.callback(null, data.data);
        }
      })
      .catch((error) => {
        console.error("Fetch error:", error);
        const errorMsg =
          (error.response &&
            error.response.data &&
            error.response.data.message) ||
          error.message ||
          "Failed to fetch data.";
        Swal.fire({
          title: "Error!",
          text: errorMsg,
          icon: "error",
        });
        this.callback(errorMsg, null);
      });
  };
}

window.GetAllRequest = GetAllRequest;
class CreateExamRequest {
  constructor({
    formSelector,
    submitButtonSelector,
    callback,
    promptMessage = "Are you sure you want to create this exam?",
    redirectUrl = null,
  }) {
    this.$form = $(formSelector);
    this.$submitButton = $(submitButtonSelector);
    this.callback = typeof callback === "function" ? callback : () => {};
    this.promptMessage = promptMessage;
    this.redirectUrl = redirectUrl;
    this.originalButtonText = this.$submitButton.html();
    this._initEvents();
  }

  _initEvents() {
    this.$form.on("submit", (e) => this._handleSubmit(e));
  }

  _toggleSpinner(show, text) {
    if (show) {
      this.$submitButton.prop("disabled", true);
      this.$submitButton.html(
        '<i class="fa-spin fa-spinner fas mr-2" aria-hidden="true"></i>' + text
      );
    } else {
      this.$submitButton.prop("disabled", false);
      this.$submitButton.html(text);
    }
  }

  _handleSubmit(e) {
    e.preventDefault();

    Swal.fire({
      title: "Are you sure?",
      text: this.promptMessage,
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Yes, create it!",
      cancelButtonText: "Cancel",
    }).then((result) => {
      if (result.isConfirmed) {
        const url = this.$form.attr("action");
        const formData = this._serializeFormToJson();

        this._toggleSpinner(true, "Submitting...");
        axios
          .post(url, formData)
          .then((response) => {
            const data = response.data;
            if (data.status === "error" || data.http_code !== 200) {
              Swal.fire({
                title: "Error!",
                text:
                  data.message ||
                  "Something went wrong. Please try again later.",
                icon: "error",
              });
              this.callback(data.message || "Unknown error", null);
            } else {
              Swal.fire({
                title: "Success!",
                text: data.message || "Your submission was successful!",
                icon: "success",
              }).then((result) => {
                if (result.isConfirmed) {
                  if (this.redirectUrl) {
                    window.location.href = this.redirectUrl;
                  } else {
                    location.reload();
                  }
                }
              });
              this.callback(null, data.data);
            }
          })
          .catch((error) => {
            const errorMsg =
              (error.response &&
                error.response.data &&
                error.response.data.message) ||
              "Something went wrong. Please try again later.";
            Swal.fire({
              title: "Error!",
              text: errorMsg,
              icon: "error",
            });
            this.callback(errorMsg, null);
          })
          .finally(() => {
            this._toggleSpinner(false, this.originalButtonText);
          });
      }
    });
  }

  _serializeFormToJson() {
    const formData = {
      exam_title: $('input[name="exam_title"]').val(),
      exam_description: $('textarea[name="exam_description"]').val(),
      exam_date: $('input[name="exam_date"]').val(),
      year_level: $('select[name="year_level"]').val(),
      question_number: [],
      question_text: [],
      options: [],
      correct_answer: [],
    };

    $('input[name="question_number[]"]').each((index, elem) => {
      formData.question_number.push($(elem).val());
      formData.question_text.push(
        $(`textarea[name="question_text[]"]`).eq(index).val()
      );
      formData.options.push({
        A: $(`input[name="options[A][]"]`).eq(index).val(),
        B: $(`input[name="options[B][]"]`).eq(index).val(),
        C: $(`input[name="options[C][]"]`).eq(index).val(),
        D: $(`input[name="options[D][]"]`).eq(index).val(),
      });
      formData.correct_answer.push(
        $(`select[name="correct_answer[]"]`).eq(index).val()
      );
    });

    return formData;
  }
}

window.CreateExamRequest = CreateExamRequest;

class CreateBatchRequest {
  constructor({
    formSelector,
    submitButtonSelector,
    callback,
    promptMessage = "Are you sure you want to add these students to the batch?",
  }) {
    this.$form = $(formSelector);
    this.$submitButton = $(submitButtonSelector);
    this.callback = typeof callback === "function" ? callback : () => {};
    this.promptMessage = promptMessage;
    this.originalButtonText = this.$submitButton.html();
    this._initEvents();
  }

  _initEvents() {
    this.$form.on("submit", (e) => this._handleSubmit(e));
  }

  _toggleSpinner(show, text) {
    if (show) {
      this.$submitButton.prop("disabled", true);
      this.$submitButton.html(
        '<i class="fa-spin fa-spinner fas mr-2" aria-hidden="true"></i>' + text
      );
    } else {
      this.$submitButton.prop("disabled", false);
      this.$submitButton.html(text);
    }
  }

  _handleSubmit(e) {
    e.preventDefault();

    Swal.fire({
      title: "Are you sure?",
      text: this.promptMessage,
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Yes, add them!",
      cancelButtonText: "Cancel",
    }).then((result) => {
      if (result.isConfirmed) {
        const url = this.$form.attr("action");
        const formData = this._serializeFormToJson();

        this._toggleSpinner(true, "Submitting...");
        axios
          .post(url, formData)
          .then((response) => {
            const data = response.data;
            if (data.status === "error" || data.http_code !== 200) {
              Swal.fire({
                title: "Error!",
                text:
                  data.message ||
                  "Something went wrong. Please try again later.",
                icon: "error",
              });
              this.callback(data.message || "Unknown error", null);
            } else {
              Swal.fire({
                title: "Success!",
                text: data.message || "Students added successfully!",
                icon: "success",
              }).then((result) => {
                if (result.isConfirmed) {
                  location.reload();
                }
              });
              this.callback(null, data.data);
            }
          })
          .catch((error) => {
            const errorMsg =
              (error.response &&
                error.response.data &&
                error.response.data.message) ||
              "Something went wrong. Please try again later.";
            Swal.fire({
              title: "Error!",
              text: errorMsg,
              icon: "error",
            });
            this.callback(errorMsg, null);
          })
          .finally(() => {
            this._toggleSpinner(false, this.originalButtonText);
          });
      }
    });
  }

  _serializeFormToJson() {
    const formData = {
      batch_id: $('select[name="add-batch"]').val(),
      student_ids: [],
    };

    $('#studentPickerBody input[type="checkbox"]:checked').each((_, elem) => {
      formData.student_ids.push($(elem).val());
    });

    return formData;
  }
}

window.CreateBatchRequest = CreateBatchRequest;
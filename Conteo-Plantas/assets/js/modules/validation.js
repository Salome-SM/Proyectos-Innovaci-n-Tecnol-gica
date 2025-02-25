export class FormValidator {
    validateForm() {
      const requiredFields = ['block_number', 'bed_number', 'year_week', 'count_date'];
      let isValid = true;
  
      requiredFields.forEach(field => {
        const element = document.getElementById(field);
        if (!element.value.trim()) {
          this.showError(element, 'This field is required');
          isValid = false;
        }
      });
  
      const videoFile = document.getElementById('video_file');
      if (!videoFile.files.length) {
        this.showError(videoFile, 'Please select a video file');
        isValid = false;
      }
  
      return isValid;
    }
  
    showError(element, message) {
      const errorDiv = document.createElement('div');
      errorDiv.className = 'form-error';
      errorDiv.textContent = message;
      element.parentNode.appendChild(errorDiv);
    }
  }
(function () {
  const MAX_SIZE_BYTES = 5 * 1024 * 1024; // 5MB
  const ALLOWED_EXTENSIONS = ['pdf', 'docx'];

  const dropzoneEmpty = document.getElementById('dropzoneEmpty');
  const dropzoneFilled = document.getElementById('dropzoneFilled');
  const resumeInput = document.getElementById('resumeInput');
  const removeFileBtn = document.getElementById('removeFileBtn');
  const fileNameEl = document.getElementById('fileName');
  const fileTypeLabel = document.getElementById('fileTypeLabel');
  const fileSizeLabel = document.getElementById('fileSizeLabel');
  const fileIconWrapper = document.getElementById('fileIconWrapper');

  function getExtension(filename) {
    return filename.split('.').pop().toLowerCase();
  }

  function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  }

  function showFilledState(file, ext) {
    fileNameEl.textContent = file.name;
    fileTypeLabel.textContent = ext;
    fileSizeLabel.textContent = formatFileSize(file.size);

    if (ext === 'pdf') {
      fileIconWrapper.className = 'w-12 h-12 mx-auto mb-3 rounded-lg flex items-center justify-center bg-red-500';
    } else {
      fileIconWrapper.className = 'w-12 h-12 mx-auto mb-3 rounded-lg flex items-center justify-center bg-blue-600';
    }

    dropzoneEmpty.classList.add('hidden');
    dropzoneFilled.classList.remove('hidden');
    dropzoneFilled.classList.add('flex');
  }

  function resetToEmptyState() {
    resumeInput.value = '';
    dropzoneFilled.classList.remove('flex');
    dropzoneFilled.classList.add('hidden');
    dropzoneEmpty.classList.remove('hidden');
  }

  function handleFile(file) {
    if (!file) return;

    const ext = getExtension(file.name);

    if (!ALLOWED_EXTENSIONS.includes(ext)) {
      showToast('Only PDF or DOCX files are supported.', 'Upload failed');
      resumeInput.value = '';
      return;
    }

    if (file.size > MAX_SIZE_BYTES) {
      showToast('File is larger than 5MB. Please upload a smaller file.', 'Upload failed');
      resumeInput.value = '';
      return;
    }

    showFilledState(file, ext);
  }

  resumeInput.addEventListener('change', (e) => {
    handleFile(e.target.files[0]);
  });

  removeFileBtn.addEventListener('click', (e) => {
    e.preventDefault();
    resetToEmptyState();
  });

  // Drag and drop support
  ['dragenter', 'dragover'].forEach(evt => {
    dropzoneEmpty.addEventListener(evt, (e) => {
      e.preventDefault();
      e.stopPropagation();
      dropzoneEmpty.classList.add('border-gray-500');
    });
  });

  ['dragleave', 'drop'].forEach(evt => {
    dropzoneEmpty.addEventListener(evt, (e) => {
      e.preventDefault();
      e.stopPropagation();
      dropzoneEmpty.classList.remove('border-gray-500');
    });
  });

  dropzoneEmpty.addEventListener('drop', (e) => {
    handleFile(e.dataTransfer.files[0]);
  });
})();
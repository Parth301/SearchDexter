function openModal(imageUrl, title) {
    document.getElementById('modalImage').src = imageUrl;
    document.getElementById('modalImage').alt = title;
    document.getElementById('modalTitle').textContent = title; // Update the title
    document.getElementById('imageModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('imageModal').style.display = 'none';
}

const voiceSearchBtn = document.getElementById('voiceSearchBtn');

voiceSearchBtn.addEventListener('click', startVoiceRecognition);

function startVoiceRecognition() {
    const recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();

    // Set recognition parameters
    recognition.lang = 'en-US';
    recognition.interimResults = false;
    recognition.maxAlternatives = 1;

    // Event listener for the recognition result
    recognition.addEventListener('result', (event) => {
        const transcript = event.results[0][0].transcript;
        document.getElementById('searchfield').value = transcript;

        // Update the document title with the voice search query
        document.title = `Voice Search: ${transcript}`;

        // Optionally, you can trigger the form submission here
        document.getElementById('searchForm').submit();
        document.getElementById('webbtn').click();
    });

    // Event listener for recognition error
    recognition.addEventListener('error', (event) => {
        console.error('Speech recognition error:', event.error);
    });

    // Event listener for recognition start
    recognition.addEventListener('start', () => {
        voiceSearchBtn.classList.add('active');
    });

    // Event listener for recognition end
    recognition.addEventListener('end', () => {
        voiceSearchBtn.classList.remove('active');
    });

    // Start speech recognition
    recognition.start();
}

function validateSearch() {
    let searchField = document.getElementById('searchfield').value.trim();

    if (searchField === '') {
        alert("Please enter a search term");
        return false; // Prevent form submission
    }

    return true; // Allow form submission
}

document.getElementById('searchfield').addEventListener('input', function () {
    showRecentSearches();
});

function showRecentSearches() {
    document.getElementById('recentSearches').style.display = 'block';
}

function useRecentSearch(recentSearch) {
    document.getElementById('searchfield').value = recentSearch;
    document.getElementById('recentSearches').style.display = 'none';
    document.getElementById('searchForm').submit(); // Submit the form
    document.getElementById('webbtn').click();
}

document.body.addEventListener('click', function (event) {
    // Check if the click event target is not the search field or recent searches box
    if (!event.target.matches('#searchfield, #recentSearches, #recentSearches *')) {
        // Hide the recent searches box
        document.getElementById('recentSearches').style.display = 'none';
    }
});

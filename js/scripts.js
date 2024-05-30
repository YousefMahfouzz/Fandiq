document.addEventListener('DOMContentLoaded', function () {
    console.log('Document loaded'); // Debugging

    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function (event) {
            form.classList.add('submitted');
        });
    }

    // Additional code for calculating the total price
    const checkInInput = document.querySelector('input[name="check_in"]');
    const checkOutInput = document.querySelector('input[name="check_out"]');
    const rateInput = document.querySelector('input[name="room_rate"]');
    const totalPriceElement = document.getElementById('total-price');
    const foodRadios = document.querySelectorAll('input[name="food"]');
    const breakfastRate = 3; // Rate for breakfast per night

    function calculateTotalPrice() {
        if (!checkInInput || !checkOutInput || !rateInput) {
            return;
        }

        const checkInDate = new Date(checkInInput.value);
        const checkOutDate = new Date(checkOutInput.value);
        const rate = parseFloat(rateInput.value);

        if (checkInDate && checkOutDate && rate) {
            const timeDifference = checkOutDate.getTime() - checkInDate.getTime();
            const daysDifference = Math.ceil(timeDifference / (1000 * 3600 * 24)); // Calculate the number of nights
            let totalPrice = daysDifference * rate;

            const selectedFood = document.querySelector('input[name="food"]:checked');
            if (selectedFood && selectedFood.value === 'Yes') {
                totalPrice += daysDifference * breakfastRate;
            }

            totalPriceElement.textContent = `${totalPrice.toFixed(2)} JD`;
        }
    }

    if (foodRadios) {
        foodRadios.forEach(radio => {
            radio.addEventListener('change', calculateTotalPrice);
        });
    }

    if (checkInInput) {
        checkInInput.addEventListener('change', calculateTotalPrice);
    }

    if (checkOutInput) {
        checkOutInput.addEventListener('change', calculateTotalPrice);
    }

    calculateTotalPrice(); // Initial calculation

    // Modal image viewer
    function showImages(imageUrls) {
        const modal = document.getElementById('imageModal');
        const modalContent = document.getElementById('modalContent');
        modalContent.innerHTML = '';

        const urls = imageUrls.split(',');
        urls.forEach(url => {
            const img = document.createElement('img');
            img.src = url.trim();
            img.className = 'modal-image';
            modalContent.appendChild(img);
        });

        modal.style.display = 'block';
    }

    function closeModal() {
        document.getElementById('imageModal').style.display = 'none';
    }

    window.showImages = showImages;
    window.closeModal = closeModal;

    // Function to show sections
    function showSection(sectionId) {
        const sections = document.querySelectorAll('.section');
        sections.forEach(section => {
            section.style.display = 'none';
        });

        document.getElementById(sectionId).style.display = 'block';
    }

    window.showSection = showSection;
});

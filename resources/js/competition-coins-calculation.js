  // Competition reward calculation
  document.addEventListener('DOMContentLoaded', function() {
    const config = JSON.parse(document.getElementById('competition-config').textContent);
    
    const winnerGiftsInput = document.getElementById('winner_gifts');
    const multiWinnerToggle = document.querySelector('input[type="checkbox"][name="multi_winner"]');            const rewardCalculation = document.getElementById('rewardCalculation');
    const firstPlaceCoins = document.getElementById('firstPlaceCoins');
    const secondPlaceRow = document.getElementById('secondPlaceRow');
    const thirdPlaceRow = document.getElementById('thirdPlaceRow');
    const secondPlaceCoins = document.getElementById('secondPlaceCoins');
    const thirdPlaceCoins = document.getElementById('thirdPlaceCoins');
    const totalCoins = document.getElementById('totalCoins');
    const balanceErrorModal = document.getElementById('balanceErrorModal');
    const requiredCoins = document.getElementById('requiredCoins');
    const availableCoins = document.getElementById('availableCoins');
    const submitButton = document.getElementById('submit-add-form-button');

    function updateRewardCalculation() {
        const winnerGifts = parseInt(winnerGiftsInput.value) || 0;
        const multiWinner = multiWinnerToggle.checked;

        if (winnerGifts > 0) {
            rewardCalculation.classList.remove('hidden');
            
            // Update 1st place
            firstPlaceCoins.textContent = winnerGifts;

            let total = 0;
            
            if (multiWinner) {
                // Calculate 2nd and 3rd place
                const secondPlace = Math.round(winnerGifts * (config.secondPlacePercentage / 100));
                const thirdPlace = Math.round(winnerGifts * (config.thirdPlacePercentage / 100));
                
                secondPlaceRow.classList.remove('hidden');
                thirdPlaceRow.classList.remove('hidden');
                
                secondPlaceCoins.textContent = secondPlace;
                thirdPlaceCoins.textContent = thirdPlace;
                
                // Update total
                total = winnerGifts + secondPlace + thirdPlace;
                totalCoins.textContent = total;
            } else {
                secondPlaceRow.classList.add('hidden');
                thirdPlaceRow.classList.add('hidden');
                
                // Update total (only 1st place)
                total = winnerGifts;
                totalCoins.textContent = winnerGifts;
            }

            if (total > config.userBalance) {
                balanceErrorModal.classList.remove('hidden');
                requiredCoins.textContent = total;
                availableCoins.textContent = config.userBalance;
                submitButton.disabled = true;
                submitButton.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                balanceErrorModal.classList.add('hidden');
                submitButton.disabled = false;
                submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        } else {
            rewardCalculation.classList.add('hidden');
        }
    }

    // Event listeners
    winnerGiftsInput.addEventListener('input', updateRewardCalculation);
    multiWinnerToggle.addEventListener('change', updateRewardCalculation);
    
    // Initial calculation
    updateRewardCalculation();
});
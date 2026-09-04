<div>
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('scroll-to-top', (event) => {
                window.scrollTo({
                    top: 0,
                    left: 0,
                    behavior: 'smooth',
                });
            });
        });
    </script>
    <script>
        function formatRupiah(number, prefix = 'Rp') {
            const numStr = number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            return prefix + numStr;
        }
    </script>
    <script>
        window.addEventListener('copy-to-clipboard', (event) => {
            const textToCopy = event.detail.text;

            if (!navigator.clipboard) {
                console.error('Clipboard API tidak didukung');
                return;
            }

            navigator.clipboard.writeText(textToCopy).then(() => {
                new FilamentNotification()
                    .title('Teks disalin!')
                    .success()
                    .duration(3000)
                    .send();
            }).catch(err => {
                new FilamentNotification()
                    .title('Gagal Menyalin')
                    .danger()
                    .send();
                console.error('Gagal menyalin teks: ', err);
            });
        });
    </script>
</div>

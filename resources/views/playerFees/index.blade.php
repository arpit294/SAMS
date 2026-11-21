<x-layout>

    <x-sidebar />

    <div class="wrapper d-flex flex-column min-vh-100 bg-body-tertiary">

        <x-navbar />

        <div class="container-fluid px-4 py-3">

            <x-table-crud-card heading="Player Fees Management" subheading="Manage Player Fees" title="Record Player Fee"
                url="{{ route('player-fees.create') }}" id="addPlayerFeeBtn" permission="fee" :filters="[
                    [
                        'id' => 'playerFilter',
                        'placeholder' => 'All Players',
                        'class' => 'select2',
                        'options' => $players,
                    ],
                    [
                        'id' => 'batchFilter',
                        'placeholder' => 'All Batches',
                        'class' => 'select2',
                        'options' => $batches,
                    ],
                    [
                        'id' => 'monthFilter',
                        'placeholder' => 'All Months',
                        'class' => 'select2',
                        'default' => date('n'),
                        'options' => [
                            '1' => 'January',
                            '2' => 'February',
                            '3' => 'March',
                            '4' => 'April',
                            '5' => 'May',
                            '6' => 'June',
                            '7' => 'July',
                            '8' => 'August',
                            '9' => 'September',
                            '10' => 'October',
                            '11' => 'November',
                            '12' => 'December',
                        ],
                    ],
                
                    [
                        'id' => 'yearFilter',
                        'placeholder' => 'All Years',
                        'default' => date('Y'),
                        'options' => $years,
                    ],
                
                    [
                        'id' => 'statusFilter',
                        'placeholder' => 'All Statuses',
                        'options' => [
                            'paid' => 'Paid',
                            'pending' => 'Pending',
                        ],
                    ],
                
                    [
                        'id' => 'paymentTypeFilter',
                        'placeholder' => 'All Payments',
                        'options' => [
                            'upi' => 'UPI',
                            'cash' => 'Cash',
                            'card' => 'Card',
                        ],
                    ],
                ]">

                {{ $dataTable->table(['class' => 'table table-sm table-hover align-middle mb-0']) }}

            </x-table-crud-card>

        </div>

    </div>

    @push('scripts')
        {{ $dataTable->scripts() }}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
        <script>
            $(document).ready(function() {
                $(document).on('click', '.download-invoice-btn', function(e) {
                    e.preventDefault();
                    var btn = $(this);
                    var url = btn.data('url');
                    
                    var originalHtml = btn.html();
                    btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
                    btn.prop('disabled', true);
                    
                    $.ajax({
                        url: url,
                        type: 'GET',
                        success: function(response) {
                            var tempDiv = $('<div>').html(response);
                            var invoiceCard = tempDiv.find('#invoice-card');
                                                        if (invoiceCard.length > 0) {
                                // Reset layout values for clean rasterization
                                invoiceCard.css({
                                    position: 'relative',
                                    top: '0',
                                    left: '0',
                                    margin: '0',
                                    width: '740px',
                                    backgroundColor: '#ffffff'
                                });
                                
                                // Wrap in a fixed offscreen wrapper container with positive coordinates
                                var wrapper = $('<div id="pdf-temp-wrapper"></div>').css({
                                    position: 'fixed',
                                    top: '0',
                                    left: '0',
                                    width: '780px',
                                    height: 'auto',
                                    zIndex: '-9999',
                                    overflow: 'hidden',
                                    backgroundColor: '#ffffff',
                                    pointerEvents: 'none'
                                });
                                
                                wrapper.append(invoiceCard);
                                $('body').append(wrapper);
                                
                                var feeId = url.split('/').slice(-2, -1)[0];
                                var padId = String(feeId).padStart(6, '0');
                                var opt = {
                                    margin:       [0.3, 0.3, 0.3, 0.3],
                                    filename:     'Invoice-INV-' + padId + '.pdf',
                                    image:        { type: 'jpeg', quality: 0.98 },
                                    html2canvas:  { scale: 2.5, useCORS: true, letterRendering: true },
                                    jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
                                };
                                
                                html2pdf().set(opt).from(invoiceCard[0]).save().then(function() {
                                    wrapper.remove();
                                    btn.html(originalHtml);
                                    btn.prop('disabled', false);
                                }).catch(function(err) {
                                    console.error('PDF Generation Error:', err);
                                    wrapper.remove();
                                    btn.html(originalHtml);
                                    btn.prop('disabled', false);
                                });
                            } else {
                                alert('Could not find invoice content.');
                                btn.html(originalHtml);
                                btn.prop('disabled', false);
                            }
                        },
                        error: function() {
                            alert('Failed to load invoice.');
                            btn.html(originalHtml);
                            btn.prop('disabled', false);
                        }
                    });
                });
            });
        </script>
    @endpush

</x-layout>

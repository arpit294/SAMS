<x-layout>
    <div class="min-vh-100 bg-light py-4 d-flex flex-column align-items-center">
        <!-- Top Toolbar (No-Print) -->
        <div class="container-lg mb-4 no-print d-flex justify-content-between align-items-center bg-white p-3 shadow-sm rounded-3">
            <div>
                <a href="{{ route('player-fees.index') }}" class="btn btn-outline-secondary btn-sm rounded-2">
                    <i class="bi bi-arrow-left me-1"></i> Back to List
                </a>
            </div>
            <div class="d-flex gap-2">
                <button onclick="downloadPDF()" class="btn btn-success btn-sm rounded-2 d-flex align-items-center gap-1 text-white" style="background-color: #10b981; border-color: #10b981;">
                    <i class="bi bi-download"></i> Download PDF
                </button>
                <button onclick="window.print()" class="btn btn-primary btn-sm rounded-2 d-flex align-items-center gap-1" style="background-color: #4f46e5; border-color: #4f46e5;">
                    <i class="bi bi-printer"></i> Print Invoice
                </button>
                <button onclick="window.close()" class="btn btn-outline-dark btn-sm rounded-2">
                    <i class="bi bi-x-lg"></i> Close
                </button>
            </div>
        </div>

        <!-- Printable Invoice Card -->
        <div class="container-lg">
            <div class="card border-0 shadow-sm p-5 invoice-card" id="invoice-card" style="border-radius: 16px; background-color: #ffffff;">
                <!-- Header -->
                <div class="row align-items-center border-bottom pb-4 mb-4">
                    <div class="col-sm-6">
                        <!-- Brand Identity -->
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="rounded-3 d-flex align-items-center justify-content-center text-white"
                                style="width: 38px; height: 38px; background-color: #4f46e5; box-shadow: 0 4px 8px rgba(79, 70, 229, 0.25);">
                                <i class="bi bi-award-fill fs-4"></i>
                            </div>
                            <div>
                                <span class="fw-bold text-dark d-block fs-5" style="letter-spacing: 0.5px; font-family: 'Plus Jakarta Sans', sans-serif; line-height: 1.2;">
                                    SPORTS ACADEMY
                                </span>
                                <span class="text-secondary fw-semibold small text-uppercase tracking-wider" style="font-size: 10px; line-height: 1;">
                                    Management System
                                </span>
                            </div>
                        </div>
                        <p class="text-secondary small mb-0">
                            123 Sports Complex, Arena Road<br>
                            support@sportsacademy.com | +91 98765 43210
                        </p>
                    </div>
                    <div class="col-sm-6 text-sm-end mt-4 mt-sm-0">
                        <h2 class="fw-bold text-dark mb-1">INVOICE</h2>
                        <p class="text-secondary mb-1">Invoice No: <span class="fw-semibold text-dark">INV-{{ str_pad($player_fee->id, 6, '0', STR_PAD_LEFT) }}</span></p>
                        <p class="text-secondary mb-2">Date: <span class="fw-semibold text-dark">{{ $player_fee->created_at->format('d M, Y') }}</span></p>
                        <span class="badge bg-success border border-light rounded-pill px-3 py-1.5 text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.5px;">
                            {{ $player_fee->status }}
                        </span>
                    </div>
                </div>

                <!-- Billing Details -->
                <div class="row mb-4 pb-2">
                    <div class="col-sm-6">
                        <span class="text-secondary text-uppercase fw-bold small d-block mb-2" style="font-size: 11px; letter-spacing: 0.5px;">
                            Billed To
                        </span>
                        <h6 class="fw-bold text-dark mb-1">{{ $player_fee->player ? ($player_fee->player->firstname . ' ' . $player_fee->player->lastname) : 'N/A' }}</h6>
                        <p class="text-secondary small mb-0">
                            Email: {{ $player_fee->player->email ?? 'N/A' }}<br>
                            Phone: {{ $player_fee->player->phone ?? 'N/A' }}<br>
                            Gender: <span class="text-capitalize">{{ $player_fee->player->gender ?? 'N/A' }}</span>
                        </p>
                    </div>
                    <div class="col-sm-6 text-sm-end mt-4 mt-sm-0">
                        <span class="text-secondary text-uppercase fw-bold small d-block mb-2" style="font-size: 11px; letter-spacing: 0.5px;">
                            Payment Details
                        </span>
                        <p class="text-secondary small mb-0">
                            Method: <span class="fw-semibold text-dark text-uppercase">{{ $player_fee->payment_type }}</span><br>
                            @if($player_fee->payment_type === 'upi' && $player_fee->upi_id)
                                UPI Transaction ID: <span class="fw-semibold text-dark">{{ $player_fee->upi_id }}</span><br>
                            @endif
                            Status: <span class="text-success fw-bold text-uppercase">Paid</span>
                        </p>
                    </div>
                </div>

                <!-- Invoice Table -->
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr class="table-light">
                                <th class="py-2.5 fw-semibold text-dark">Description</th>
                                <th class="py-2.5 text-center fw-semibold text-dark" style="width: 250px;">Billing Period</th>
                                <th class="py-2.5 text-end fw-semibold text-dark" style="width: 180px;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="py-3">
                                    <h6 class="fw-semibold text-dark mb-1">Academy Enrollment & Coaching Fees</h6>
                                    <p class="text-secondary small mb-0">
                                        Batch: <span class="fw-semibold text-dark">{{ $player_fee->batch->name ?? 'N/A' }}</span> 
                                        ({{ ($player_fee->batch->sport->name ?? 'N/A') . ' - ' . ($player_fee->batch->level->name ?? 'N/A') }})
                                    </p>
                                </td>
                                <td class="text-center py-3">
                                    <span class="fw-semibold text-dark">{{ $player_fee->start_date ? $player_fee->start_date->format('M Y') : 'N/A' }}</span>
                                    <span class="text-muted mx-1">to</span>
                                    <span class="fw-semibold text-dark">{{ $player_fee->end_date ? $player_fee->end_date->format('M Y') : 'N/A' }}</span>
                                </td>
                                <td class="text-end py-3 fw-semibold text-dark">
                                    ₹{{ number_format($player_fee->sub_totalamount, 2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Summary -->
                <div class="row justify-content-end mb-4">
                    <div class="col-md-5 col-sm-7">
                        <table class="table table-borderless table-sm mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-secondary py-1.5">Subtotal:</td>
                                    <td class="text-end text-dark fw-semibold py-1.5">₹{{ number_format($player_fee->sub_totalamount, 2) }}</td>
                                </tr>
                                @if(floatval($player_fee->discount_amount) > 0)
                                    <tr>
                                        <td class="text-secondary py-1.5">Discount:</td>
                                        <td class="text-end text-success fw-semibold py-1.5">-₹{{ number_format($player_fee->discount_amount, 2) }}</td>
                                    </tr>
                                @endif
                                @if(floatval($player_fee->penalty_amount) > 0)
                                    <tr>
                                        <td class="text-secondary py-1.5">Late Penalty:</td>
                                        <td class="text-end text-danger fw-semibold py-1.5">+₹{{ number_format($player_fee->penalty_amount, 2) }}</td>
                                    </tr>
                                @endif
                                <tr class="border-top">
                                    <td class="text-dark fw-bold py-2 fs-5">Total Paid:</td>
                                    <td class="text-end text-dark fw-bold py-2 fs-5">₹{{ number_format($player_fee->total_amt, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Footer stamp / sign -->
                <div class="row align-items-end mt-5 pt-4 border-top">
                    <div class="col-sm-7">
                        <h6 class="fw-bold text-dark mb-2">Terms & Notes</h6>
                        <p class="text-secondary small mb-0" style="max-width: 420px; line-height: 1.5;">
                            This invoice is a digitally generated payment confirmation and receipt. All payments made are subject to academy enrollment policies and conditions.
                        </p>
                    </div>
                    <div class="col-sm-5 text-sm-end mt-4 mt-sm-0">
                        <div class="d-inline-block text-center">
                            <div class="px-4 py-2 bg-success bg-opacity-10 text-success rounded-3 border border-success border-opacity-20 mb-2" style="font-family: 'Courier New', Courier, monospace; transform: rotate(-3deg); font-size: 15px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px;">
                                PAID RECEIVED
                            </div>
                            <p class="text-secondary small mb-0">Sports Academy Authority</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Specific CSS -->
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body, .min-vh-100 {
                background-color: #fff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .invoice-card {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
            }
        }
    </style>

    <!-- html2pdf.js library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadPDF() {
            const element = document.getElementById('invoice-card');
            const originalWidth = element.style.width;
            element.style.width = '740px';
            
            const opt = {
                margin:       [0.3, 0.3, 0.3, 0.3],
                filename:     'Invoice-INV-{{ str_pad($player_fee->id, 6, "0", STR_PAD_LEFT) }}.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2.5, useCORS: true, letterRendering: true },
                jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
            };
            
            return html2pdf().set(opt).from(element).save().then(() => {
                element.style.width = originalWidth;
            }).catch((err) => {
                element.style.width = originalWidth;
                throw err;
            });
        }

        @if(request()->query('download') == 1)
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                downloadPDF().then(() => {
                    setTimeout(() => {
                        window.close();
                    }, 500);
                });
            }, 600);
        });
        @endif
    </script>
</x-layout>

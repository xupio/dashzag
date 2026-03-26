<form method="post" action="{{ route('profile.update') }}" class="space-y-4">
    @csrf
    @method('patch')

    <p class="text-secondary mb-4">
        Save your own withdrawal wallets here. These are your personal payout destinations for withdrawals only, and they are separate from the admin treasury wallets used to receive client payments.
    </p>

    <div class="mb-3">
        <label for="btc_wallet_address" class="form-label">BTC Wallet Address</label>
        <input
            id="btc_wallet_address"
            name="btc_wallet_address"
            type="text"
            class="form-control @error('btc_wallet_address') is-invalid @enderror"
            value="{{ old('btc_wallet_address', $user->btc_wallet_address) }}"
            placeholder="bc1..."
        >
        @error('btc_wallet_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label for="usdt_wallet_address" class="form-label">USDT Wallet Address</label>
        <input
            id="usdt_wallet_address"
            name="usdt_wallet_address"
            type="text"
            class="form-control @error('usdt_wallet_address') is-invalid @enderror"
            value="{{ old('usdt_wallet_address', $user->usdt_wallet_address) }}"
            placeholder="T... or 0x..."
        >
        <div class="form-text">Use the exact address and network where you want to receive your USDT withdrawals.</div>
        @error('usdt_wallet_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label for="bank_transfer_details" class="form-label">Bank Transfer Details</label>
        <textarea
            id="bank_transfer_details"
            name="bank_transfer_details"
            rows="4"
            class="form-control @error('bank_transfer_details') is-invalid @enderror"
            placeholder="Beneficiary name, bank name, IBAN/account number, SWIFT"
        >{{ old('bank_transfer_details', $user->bank_transfer_details) }}</textarea>
        <div class="form-text">Add the bank account details where you want ZagChain to send your payout.</div>
        @error('bank_transfer_details')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">Save my withdrawal wallets</button>
    </div>
</form>

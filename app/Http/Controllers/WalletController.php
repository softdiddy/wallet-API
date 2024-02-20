<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Create a new user
        $user = User::create([
            'email' => $request->input('email'),
            'password' => bcrypt($request->input('password')),
        ]);

        // Generate API key for the user
        $apiKey = bcrypt(now() . $user->id . $user->email);
        $user->update(['api_key' => $apiKey]);

        return response()->json(['api_key' => $apiKey], 201);
    }

    public function createWallet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currency' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Assuming user is authenticated using the API key
        $user = $request->user();

        // Create a wallet for the user
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'currency' => $request->input('currency'),
        ]);

        return response()->json($wallet, 201);
    }

    public function debitWallet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wallet_id' => 'required|exists:wallets,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Assuming user is authenticated using the API key
        $user = $request->user();

        // Check if the wallet belongs to the authenticated user
        $wallet = Wallet::find($request->input('wallet_id'));
        if (!$wallet || $wallet->user_id != $user->id) {
            return response()->json(['error' => 'Invalid wallet'], 400);
        }

        // Debit the wallet
        $transaction = Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'debit',
            'amount' => $request->input('amount'),
        ]);

        // Update wallet balance
        $wallet->update(['balance' => $wallet->balance - $request->input('amount')]);

        return response()->json($transaction, 201);
    }

    public function getTransactionHistory(Request $request)
    {
        // Assuming user is authenticated using the API key
        $user = $request->user();

        // Retrieve transaction history for all wallets belonging to the user
        $transactions = Transaction::whereIn('wallet_id', $user->wallets()->pluck('id'))
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($transactions);
    }
}

?>
#![no_std]

use soroban_sdk::{contract, contractimpl, contracttype, Address, Env, String, Symbol};

#[derive(Clone)]
#[contracttype]
pub struct TipReceipt {
    pub tip_id: u64,
    pub sender: Address,
    pub receiver: Address,
    pub asset_code: String,
    pub asset_issuer: String,
    pub gross_amount: i128,
    pub platform_fee: i128,
    pub creator_payout_amount: i128,
    pub stellar_payment_tx_hash: String,
    pub timestamp: u64,
}

#[derive(Clone)]
#[contracttype]
pub struct CreatorStats {
    pub tip_count: u64,
    pub gross_amount: i128,
    pub creator_payout_amount: i128,
    pub platform_fee: i128,
}

#[derive(Clone)]
#[contracttype]
pub enum DataKey {
    Admin,
    Tip(u64),
    Creator(Address),
}

#[contract]
pub struct TipRegistry;

#[contractimpl]
impl TipRegistry {
    pub fn initialize(env: Env, admin: Address) {
        if env.storage().instance().has(&DataKey::Admin) {
            panic!("already initialized");
        }

        admin.require_auth();
        env.storage().instance().set(&DataKey::Admin, &admin);
    }

    #[allow(clippy::too_many_arguments)]
    pub fn record_tip(
        env: Env,
        tip_id: u64,
        sender: Address,
        receiver: Address,
        asset_code: String,
        asset_issuer: String,
        gross_amount: i128,
        platform_fee: i128,
        creator_payout_amount: i128,
        stellar_payment_tx_hash: String,
        timestamp: u64,
    ) {
        let admin: Address = env.storage().instance().get(&DataKey::Admin).expect("not initialized");
        admin.require_auth();

        let tip_key = DataKey::Tip(tip_id);
        if env.storage().persistent().has(&tip_key) {
            panic!("tip already recorded");
        }

        let receipt = TipReceipt {
            tip_id,
            sender: sender.clone(),
            receiver: receiver.clone(),
            asset_code: asset_code.clone(),
            asset_issuer: asset_issuer.clone(),
            gross_amount,
            platform_fee,
            creator_payout_amount,
            stellar_payment_tx_hash: stellar_payment_tx_hash.clone(),
            timestamp,
        };

        env.storage().persistent().set(&tip_key, &receipt);

        let stats_key = DataKey::Creator(receiver.clone());
        let mut stats = env
            .storage()
            .persistent()
            .get::<DataKey, CreatorStats>(&stats_key)
            .unwrap_or(CreatorStats {
                tip_count: 0,
                gross_amount: 0,
                creator_payout_amount: 0,
                platform_fee: 0,
            });

        stats.tip_count += 1;
        stats.gross_amount += gross_amount;
        stats.creator_payout_amount += creator_payout_amount;
        stats.platform_fee += platform_fee;
        env.storage().persistent().set(&stats_key, &stats);

        env.events().publish(
            (Symbol::new(&env, "tip_recorded"), receiver),
            (tip_id, sender, asset_code, gross_amount, stellar_payment_tx_hash),
        );
    }

    pub fn get_tip(env: Env, tip_id: u64) -> Option<TipReceipt> {
        env.storage().persistent().get(&DataKey::Tip(tip_id))
    }

    pub fn get_creator_stats(env: Env, receiver: Address) -> CreatorStats {
        env.storage()
            .persistent()
            .get(&DataKey::Creator(receiver))
            .unwrap_or(CreatorStats {
                tip_count: 0,
                gross_amount: 0,
                creator_payout_amount: 0,
                platform_fee: 0,
            })
    }
}

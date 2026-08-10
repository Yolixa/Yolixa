use soroban_sdk::{contracttype, Address, Env};

use crate::{
    error::Error,
    types::{CreatorStats, TipReceipt},
};

const DAY_IN_LEDGERS: u32 = 17_280;
const INSTANCE_TTL_THRESHOLD: u32 = 30 * DAY_IN_LEDGERS;
const INSTANCE_TTL_EXTEND_TO: u32 = 120 * DAY_IN_LEDGERS;
const PERSISTENT_TTL_THRESHOLD: u32 = 30 * DAY_IN_LEDGERS;
const PERSISTENT_TTL_EXTEND_TO: u32 = 180 * DAY_IN_LEDGERS;

#[derive(Clone)]
#[contracttype]
pub enum DataKey {
    Admin,
    Treasury,
    FeeBps,
    Paused,
    Token(Address),
    Tip(u64),
    Creator(Address),
}

pub fn bump_instance(env: &Env) {
    env.storage()
        .instance()
        .extend_ttl(INSTANCE_TTL_THRESHOLD, INSTANCE_TTL_EXTEND_TO);
}

fn bump_persistent(env: &Env, key: &DataKey) {
    env.storage()
        .persistent()
        .extend_ttl(key, PERSISTENT_TTL_THRESHOLD, PERSISTENT_TTL_EXTEND_TO);
}

pub fn is_initialized(env: &Env) -> bool {
    env.storage().instance().has(&DataKey::Admin)
}

pub fn set_admin(env: &Env, admin: &Address) {
    env.storage().instance().set(&DataKey::Admin, admin);
    bump_instance(env);
}

pub fn get_admin(env: &Env) -> Result<Address, Error> {
    env.storage()
        .instance()
        .get(&DataKey::Admin)
        .ok_or(Error::NotInitialized)
}

pub fn set_treasury(env: &Env, treasury: &Address) {
    env.storage().instance().set(&DataKey::Treasury, treasury);
    bump_instance(env);
}

pub fn get_treasury(env: &Env) -> Result<Address, Error> {
    env.storage()
        .instance()
        .get(&DataKey::Treasury)
        .ok_or(Error::NotInitialized)
}

pub fn set_fee_bps(env: &Env, fee_bps: u32) {
    env.storage().instance().set(&DataKey::FeeBps, &fee_bps);
    bump_instance(env);
}

pub fn get_fee_bps(env: &Env) -> Result<u32, Error> {
    env.storage()
        .instance()
        .get(&DataKey::FeeBps)
        .ok_or(Error::NotInitialized)
}

pub fn set_paused(env: &Env, paused: bool) {
    env.storage().instance().set(&DataKey::Paused, &paused);
    bump_instance(env);
}

pub fn is_paused(env: &Env) -> bool {
    env.storage()
        .instance()
        .get(&DataKey::Paused)
        .unwrap_or(false)
}

pub fn set_token_enabled(env: &Env, token: &Address, enabled: bool) {
    let key = DataKey::Token(token.clone());
    env.storage().persistent().set(&key, &enabled);
    bump_persistent(env, &key);
}

pub fn is_token_enabled(env: &Env, token: &Address) -> bool {
    let key = DataKey::Token(token.clone());
    let enabled = env.storage().persistent().get(&key).unwrap_or(false);
    if enabled {
        bump_persistent(env, &key);
    }
    enabled
}

pub fn tip_exists(env: &Env, tip_id: u64) -> bool {
    let key = DataKey::Tip(tip_id);
    let exists = env.storage().persistent().has(&key);
    if exists {
        bump_persistent(env, &key);
    }
    exists
}

pub fn put_tip(env: &Env, receipt: &TipReceipt) {
    let key = DataKey::Tip(receipt.tip_id);
    env.storage().persistent().set(&key, receipt);
    bump_persistent(env, &key);
}

pub fn get_tip(env: &Env, tip_id: u64) -> Option<TipReceipt> {
    let key = DataKey::Tip(tip_id);
    let receipt = env.storage().persistent().get(&key);
    if receipt.is_some() {
        bump_persistent(env, &key);
    }
    receipt
}

pub fn get_creator_stats(env: &Env, creator: &Address) -> CreatorStats {
    let key = DataKey::Creator(creator.clone());
    let stats = env
        .storage()
        .persistent()
        .get(&key)
        .unwrap_or_else(CreatorStats::empty);
    if stats.tip_count > 0 {
        bump_persistent(env, &key);
    }
    stats
}

pub fn put_creator_stats(env: &Env, creator: &Address, stats: &CreatorStats) {
    let key = DataKey::Creator(creator.clone());
    env.storage().persistent().set(&key, stats);
    bump_persistent(env, &key);
}

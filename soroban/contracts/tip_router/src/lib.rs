#![no_std]

mod error;
mod storage;
mod types;

pub use error::Error;
pub use types::{CreatorStats, SplitPayout, SplitRecipient, TipReceipt};

use soroban_sdk::{contract, contractimpl, token::TokenClient, Address, Env, Vec};

use crate::types::{
    FeeUpdatedEvent, PauseStatusChangedEvent, SplitTipEvent, TipEvent, TokenStatusChangedEvent,
    TreasuryUpdatedEvent,
};

const BPS_DENOMINATOR: i128 = 10_000;
pub const MAX_FEE_BPS: u32 = 300;
pub const MAX_SPLIT_RECIPIENTS: u32 = 10;

#[contract]
pub struct YolixaTipRouter;

#[contractimpl]
impl YolixaTipRouter {
    pub fn initialize(
        env: Env,
        admin: Address,
        treasury: Address,
        fee_bps: u32,
    ) -> Result<(), Error> {
        if storage::is_initialized(&env) {
            return Err(Error::AlreadyInitialized);
        }
        validate_fee(fee_bps)?;

        admin.require_auth();
        storage::set_admin(&env, &admin);
        storage::set_treasury(&env, &treasury);
        storage::set_fee_bps(&env, fee_bps);
        storage::set_paused(&env, false);
        Ok(())
    }

    pub fn set_token(
        env: Env,
        admin: Address,
        token: Address,
        enabled: bool,
    ) -> Result<(), Error> {
        require_admin(&env, &admin)?;
        let previous_enabled = storage::is_token_enabled(&env, &token);
        storage::set_token_enabled(&env, &token, enabled);
        TokenStatusChangedEvent {
            admin,
            token,
            previous_enabled,
            enabled,
        }
        .publish(&env);
        Ok(())
    }

    pub fn is_token_enabled(env: Env, token: Address) -> bool {
        storage::is_token_enabled(&env, &token)
    }

    pub fn set_fee(env: Env, admin: Address, new_fee_bps: u32) -> Result<(), Error> {
        require_admin(&env, &admin)?;
        validate_fee(new_fee_bps)?;
        let previous_fee_bps = storage::get_fee_bps(&env)?;
        storage::set_fee_bps(&env, new_fee_bps);
        FeeUpdatedEvent {
            admin,
            previous_fee_bps,
            new_fee_bps,
        }
        .publish(&env);
        Ok(())
    }

    pub fn set_treasury(env: Env, admin: Address, treasury: Address) -> Result<(), Error> {
        require_admin(&env, &admin)?;
        let previous_treasury = storage::get_treasury(&env)?;
        storage::set_treasury(&env, &treasury);
        TreasuryUpdatedEvent {
            admin,
            previous_treasury,
            new_treasury: treasury,
        }
        .publish(&env);
        Ok(())
    }

    pub fn pause(env: Env, admin: Address) -> Result<(), Error> {
        require_admin(&env, &admin)?;
        let previous_paused = storage::is_paused(&env);
        storage::set_paused(&env, true);
        PauseStatusChangedEvent {
            admin,
            previous_paused,
            paused: true,
        }
        .publish(&env);
        Ok(())
    }

    pub fn unpause(env: Env, admin: Address) -> Result<(), Error> {
        require_admin(&env, &admin)?;
        let previous_paused = storage::is_paused(&env);
        storage::set_paused(&env, false);
        PauseStatusChangedEvent {
            admin,
            previous_paused,
            paused: false,
        }
        .publish(&env);
        Ok(())
    }

    pub fn is_paused(env: Env) -> bool {
        storage::is_paused(&env)
    }

    pub fn tip(
        env: Env,
        sender: Address,
        creator: Address,
        token: Address,
        amount: i128,
        tip_id: u64,
    ) -> Result<(), Error> {
        ensure_payments_open(&env)?;
        if sender == creator {
            return Err(Error::SelfTip);
        }

        let (platform_fee, creator_amount) = calculate_fee_split(&env, amount)?;
        ensure_token_supported(&env, &token)?;
        ensure_new_tip(&env, &sender, tip_id)?;

        sender.require_auth();

        let receipt = TipReceipt {
            tip_id,
            sender: sender.clone(),
            token: token.clone(),
            creator: Some(creator.clone()),
            gross_amount: amount,
            creator_amount,
            platform_fee,
            recipient_count: 1,
        };
        storage::put_tip(&env, &receipt);

        let token_client = TokenClient::new(&env, &token);
        token_client.transfer(&sender, creator.clone(), &creator_amount);
        transfer_platform_fee(&env, &token_client, &sender, platform_fee)?;

        add_creator_stats(&env, &creator, amount, creator_amount)?;

        TipEvent {
            tip_id,
            sender,
            creator,
            token,
            gross_amount: amount,
            creator_amount,
            platform_fee,
        }
        .publish(&env);

        Ok(())
    }

    pub fn tip_split(
        env: Env,
        sender: Address,
        recipients: Vec<SplitRecipient>,
        token: Address,
        amount: i128,
        tip_id: u64,
    ) -> Result<(), Error> {
        ensure_payments_open(&env)?;
        let (platform_fee, creator_amount) = calculate_fee_split(&env, amount)?;
        ensure_token_supported(&env, &token)?;
        ensure_new_tip(&env, &sender, tip_id)?;
        validate_recipients(&sender, &recipients)?;

        sender.require_auth();

        let payouts = calculate_split_payouts(&env, amount, creator_amount, &recipients)?;
        let receipt = TipReceipt {
            tip_id,
            sender: sender.clone(),
            token: token.clone(),
            creator: None,
            gross_amount: amount,
            creator_amount,
            platform_fee,
            recipient_count: recipients.len(),
        };
        storage::put_tip(&env, &receipt);

        let token_client = TokenClient::new(&env, &token);
        for payout in payouts.iter() {
            if payout.net_amount > 0 {
                token_client.transfer(&sender, payout.recipient.clone(), &payout.net_amount);
            }
            add_creator_stats(
                &env,
                &payout.recipient,
                payout.gross_amount,
                payout.net_amount,
            )?;
        }
        transfer_platform_fee(&env, &token_client, &sender, platform_fee)?;

        SplitTipEvent {
            tip_id,
            sender,
            token,
            gross_amount: amount,
            creator_amount,
            platform_fee,
            payouts,
        }
        .publish(&env);

        Ok(())
    }

    pub fn get_creator_stats(env: Env, creator: Address) -> CreatorStats {
        storage::get_creator_stats(&env, &creator)
    }

    pub fn tip_exists(env: Env, sender: Address, tip_id: u64) -> bool {
        storage::tip_exists(&env, &sender, tip_id)
    }

    pub fn get_tip(env: Env, sender: Address, tip_id: u64) -> Option<TipReceipt> {
        storage::get_tip(&env, &sender, tip_id)
    }

    pub fn get_admin(env: Env) -> Result<Address, Error> {
        storage::get_admin(&env)
    }

    pub fn get_treasury(env: Env) -> Result<Address, Error> {
        storage::get_treasury(&env)
    }

    pub fn get_fee_bps(env: Env) -> Result<u32, Error> {
        storage::get_fee_bps(&env)
    }
}

fn validate_fee(fee_bps: u32) -> Result<(), Error> {
    if fee_bps > MAX_FEE_BPS {
        return Err(Error::InvalidFee);
    }
    Ok(())
}

fn require_admin(env: &Env, admin: &Address) -> Result<(), Error> {
    let stored_admin = storage::get_admin(env)?;
    if stored_admin != *admin {
        return Err(Error::Unauthorized);
    }
    admin.require_auth();
    storage::bump_instance(env);
    Ok(())
}

fn ensure_payments_open(env: &Env) -> Result<(), Error> {
    if !storage::is_initialized(env) {
        return Err(Error::NotInitialized);
    }
    storage::bump_instance(env);
    if storage::is_paused(env) {
        return Err(Error::Paused);
    }
    Ok(())
}

fn ensure_token_supported(env: &Env, token: &Address) -> Result<(), Error> {
    if !storage::is_token_enabled(env, token) {
        return Err(Error::UnsupportedToken);
    }
    Ok(())
}

fn ensure_new_tip(env: &Env, sender: &Address, tip_id: u64) -> Result<(), Error> {
    if storage::tip_exists(env, sender, tip_id) {
        return Err(Error::DuplicateTip);
    }
    Ok(())
}

fn calculate_fee_split(env: &Env, amount: i128) -> Result<(i128, i128), Error> {
    if amount <= 0 {
        return Err(Error::InvalidAmount);
    }

    let fee_bps = storage::get_fee_bps(env)? as i128;
    let platform_fee = bps_amount(amount, fee_bps)?;
    let creator_amount = amount
        .checked_sub(platform_fee)
        .ok_or(Error::MathOverflow)?;

    if creator_amount <= 0 {
        return Err(Error::InvalidPayout);
    }

    Ok((platform_fee, creator_amount))
}

fn bps_amount(amount: i128, bps: i128) -> Result<i128, Error> {
    amount
        .checked_mul(bps)
        .ok_or(Error::MathOverflow)
        .map(|value| value / BPS_DENOMINATOR)
}

fn validate_recipients(sender: &Address, recipients: &Vec<SplitRecipient>) -> Result<(), Error> {
    let recipient_count = recipients.len();
    if recipient_count == 0 {
        return Err(Error::InvalidSplit);
    }
    if recipient_count > MAX_SPLIT_RECIPIENTS {
        return Err(Error::TooManyRecipients);
    }

    let mut total_bps: u32 = 0;
    let mut i = 0;
    while i < recipient_count {
        let current = recipients
            .try_get_unchecked(i)
            .map_err(|_| Error::InvalidSplit)?;
        if current.bps == 0 {
            return Err(Error::InvalidSplit);
        }
        if current.recipient == *sender {
            return Err(Error::SelfTip);
        }

        total_bps = total_bps
            .checked_add(current.bps)
            .ok_or(Error::InvalidSplit)?;

        let mut j = i + 1;
        while j < recipient_count {
            let candidate = recipients
                .try_get_unchecked(j)
                .map_err(|_| Error::InvalidSplit)?;
            if current.recipient == candidate.recipient {
                return Err(Error::DuplicateRecipient);
            }
            j += 1;
        }
        i += 1;
    }

    if total_bps != BPS_DENOMINATOR as u32 {
        return Err(Error::InvalidSplit);
    }

    Ok(())
}

fn calculate_split_payouts(
    env: &Env,
    gross_amount: i128,
    creator_amount: i128,
    recipients: &Vec<SplitRecipient>,
) -> Result<Vec<SplitPayout>, Error> {
    let mut payouts = Vec::new(env);
    let mut gross_distributed: i128 = 0;
    let mut net_distributed: i128 = 0;
    let mut i = 0;
    let recipient_count = recipients.len();

    while i < recipient_count {
        let recipient = recipients
            .try_get_unchecked(i)
            .map_err(|_| Error::InvalidSplit)?;
        let bps = recipient.bps as i128;
        let gross_share = bps_amount(gross_amount, bps)?;
        let net_share = bps_amount(creator_amount, bps)?;

        gross_distributed = gross_distributed
            .checked_add(gross_share)
            .ok_or(Error::MathOverflow)?;
        net_distributed = net_distributed
            .checked_add(net_share)
            .ok_or(Error::MathOverflow)?;

        payouts.push_back(SplitPayout {
            recipient: recipient.recipient,
            bps: recipient.bps,
            gross_amount: gross_share,
            net_amount: net_share,
        });
        i += 1;
    }

    let gross_remainder = gross_amount
        .checked_sub(gross_distributed)
        .ok_or(Error::MathOverflow)?;
    let net_remainder = creator_amount
        .checked_sub(net_distributed)
        .ok_or(Error::MathOverflow)?;

    if gross_remainder > 0 || net_remainder > 0 {
        let mut first = payouts.get_unchecked(0);
        first.gross_amount = first
            .gross_amount
            .checked_add(gross_remainder)
            .ok_or(Error::MathOverflow)?;
        first.net_amount = first
            .net_amount
            .checked_add(net_remainder)
            .ok_or(Error::MathOverflow)?;
        payouts.set(0, first);
    }

    Ok(payouts)
}

fn transfer_platform_fee(
    env: &Env,
    token_client: &TokenClient,
    sender: &Address,
    platform_fee: i128,
) -> Result<(), Error> {
    if platform_fee > 0 {
        let treasury = storage::get_treasury(env)?;
        token_client.transfer(sender, treasury, &platform_fee);
    }
    Ok(())
}

fn add_creator_stats(
    env: &Env,
    creator: &Address,
    gross_amount: i128,
    net_amount: i128,
) -> Result<(), Error> {
    let mut stats = storage::get_creator_stats(env, creator);
    stats.tip_count = stats.tip_count.checked_add(1).ok_or(Error::MathOverflow)?;
    stats.gross_received = stats
        .gross_received
        .checked_add(gross_amount)
        .ok_or(Error::MathOverflow)?;
    stats.net_received = stats
        .net_received
        .checked_add(net_amount)
        .ok_or(Error::MathOverflow)?;
    storage::put_creator_stats(env, creator, &stats);
    Ok(())
}

#[cfg(test)]
mod test;

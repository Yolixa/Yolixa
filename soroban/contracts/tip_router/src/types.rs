use soroban_sdk::{contractevent, contracttype, Address, Vec};

#[derive(Clone)]
#[contracttype]
pub struct CreatorStats {
    pub tip_count: u64,
    pub gross_received: i128,
    pub net_received: i128,
}

impl CreatorStats {
    pub fn empty() -> Self {
        Self {
            tip_count: 0,
            gross_received: 0,
            net_received: 0,
        }
    }
}

#[derive(Clone)]
#[contracttype]
pub struct TipReceipt {
    pub tip_id: u64,
    pub sender: Address,
    pub token: Address,
    pub creator: Option<Address>,
    pub gross_amount: i128,
    pub creator_amount: i128,
    pub platform_fee: i128,
    pub recipient_count: u32,
}

#[derive(Clone)]
#[contracttype]
pub struct SplitRecipient {
    pub recipient: Address,
    pub bps: u32,
}

#[derive(Clone, Debug, Eq, PartialEq)]
#[contracttype]
pub struct SplitPayout {
    pub recipient: Address,
    pub bps: u32,
    pub gross_amount: i128,
    pub net_amount: i128,
}

#[contractevent(topics = ["tip"])]
#[derive(Clone, Debug, Eq, PartialEq)]
pub struct TipEvent {
    #[topic]
    pub tip_id: u64,
    #[topic]
    pub sender: Address,
    #[topic]
    pub creator: Address,
    pub token: Address,
    pub gross_amount: i128,
    pub creator_amount: i128,
    pub platform_fee: i128,
}

#[contractevent(topics = ["split_tip"])]
#[derive(Clone, Debug, Eq, PartialEq)]
pub struct SplitTipEvent {
    #[topic]
    pub tip_id: u64,
    #[topic]
    pub sender: Address,
    pub token: Address,
    pub gross_amount: i128,
    pub creator_amount: i128,
    pub platform_fee: i128,
    pub payouts: Vec<SplitPayout>,
}

#[contractevent(topics = ["fee_updated"])]
#[derive(Clone, Debug, Eq, PartialEq)]
pub struct FeeUpdatedEvent {
    #[topic]
    pub admin: Address,
    pub previous_fee_bps: u32,
    pub new_fee_bps: u32,
}

#[contractevent(topics = ["treasury_updated"])]
#[derive(Clone, Debug, Eq, PartialEq)]
pub struct TreasuryUpdatedEvent {
    #[topic]
    pub admin: Address,
    pub previous_treasury: Address,
    pub new_treasury: Address,
}

#[contractevent(topics = ["token_status_changed"])]
#[derive(Clone, Debug, Eq, PartialEq)]
pub struct TokenStatusChangedEvent {
    #[topic]
    pub admin: Address,
    #[topic]
    pub token: Address,
    pub previous_enabled: bool,
    pub enabled: bool,
}

#[contractevent(topics = ["pause_status_changed"])]
#[derive(Clone, Debug, Eq, PartialEq)]
pub struct PauseStatusChangedEvent {
    #[topic]
    pub admin: Address,
    pub previous_paused: bool,
    pub paused: bool,
}

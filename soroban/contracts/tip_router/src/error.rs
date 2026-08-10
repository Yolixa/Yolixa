use soroban_sdk::contracterror;

#[contracterror]
#[derive(Copy, Clone, Debug, Eq, PartialEq, PartialOrd, Ord)]
#[repr(u32)]
pub enum Error {
    AlreadyInitialized = 1,
    NotInitialized = 2,
    Unauthorized = 3,
    InvalidAmount = 4,
    SelfTip = 5,
    UnsupportedToken = 6,
    DuplicateTip = 7,
    InvalidFee = 8,
    Paused = 9,
    InvalidSplit = 10,
    TooManyRecipients = 11,
    DuplicateRecipient = 12,
    InvalidPayout = 13,
    MathOverflow = 14,
}

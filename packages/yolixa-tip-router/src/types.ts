import {Address} from '@stellar/stellar-sdk';

    /**
 * Error Enum: Error
 */
export const Error = {
  1 : { message: "AlreadyInitialized" },
  2 : { message: "NotInitialized" },
  3 : { message: "Unauthorized" },
  4 : { message: "InvalidAmount" },
  5 : { message: "SelfTip" },
  6 : { message: "UnsupportedToken" },
  7 : { message: "DuplicateTip" },
  8 : { message: "InvalidFee" },
  9 : { message: "Paused" },
  10 : { message: "InvalidSplit" },
  11 : { message: "TooManyRecipients" },
  12 : { message: "DuplicateRecipient" },
  13 : { message: "InvalidPayout" },
  14 : { message: "MathOverflow" }
}

/**
 * Event: TipEvent
 */
export interface TipEventEvent {
  name: "TipEvent";
  data: {
    tip_id: bigint;
    sender: string;
    creator: string;
    token?: string;
    gross_amount?: bigint;
    creator_amount?: bigint;
    platform_fee?: bigint;
  };
}

/**
 * Struct: TipReceipt
 */
export interface TipReceipt {
  creator: string | null;
  creator_amount: bigint;
  gross_amount: bigint;
  platform_fee: bigint;
  recipient_count: number;
  sender: string;
  tip_id: bigint;
  token: string;
}

/**
 * Struct: SplitPayout
 */
export interface SplitPayout {
  bps: number;
  gross_amount: bigint;
  net_amount: bigint;
  recipient: string;
}

/**
 * Struct: CreatorStats
 */
export interface CreatorStats {
  gross_received: bigint;
  net_received: bigint;
  tip_count: bigint;
}

/**
 * Event: SplitTipEvent
 */
export interface SplitTipEventEvent {
  name: "SplitTipEvent";
  data: {
    tip_id: bigint;
    sender: string;
    token?: string;
    gross_amount?: bigint;
    creator_amount?: bigint;
    platform_fee?: bigint;
    payouts?: Array<SplitPayout>;
  };
}

/**
 * Struct: SplitRecipient
 */
export interface SplitRecipient {
  bps: number;
  recipient: string;
}

/**
 * Event: FeeUpdatedEvent
 */
export interface FeeUpdatedEventEvent {
  name: "FeeUpdatedEvent";
  data: {
    admin: string;
    previous_fee_bps?: number;
    new_fee_bps?: number;
  };
}

/**
 * Event: TreasuryUpdatedEvent
 */
export interface TreasuryUpdatedEventEvent {
  name: "TreasuryUpdatedEvent";
  data: {
    admin: string;
    previous_treasury?: string;
    new_treasury?: string;
  };
}

/**
 * Event: PauseStatusChangedEvent
 */
export interface PauseStatusChangedEventEvent {
  name: "PauseStatusChangedEvent";
  data: {
    admin: string;
    previous_paused?: boolean;
    paused?: boolean;
  };
}

/**
 * Event: TokenStatusChangedEvent
 */
export interface TokenStatusChangedEventEvent {
  name: "TokenStatusChangedEvent";
  data: {
    admin: string;
    token: string;
    previous_enabled?: boolean;
    enabled?: boolean;
  };
}

/**
 * Union: DataKey
 */
export type DataKey =
  { tag: "Admin"; values: void } |
  { tag: "Treasury"; values: void } |
  { tag: "FeeBps"; values: void } |
  { tag: "Paused"; values: void } |
  { tag: "Token"; values: readonly [string] } |
  { tag: "Tip"; values: readonly [string, bigint] } |
  { tag: "Creator"; values: readonly [string] };
export type ContractEvent = TipEventEvent | SplitTipEventEvent | FeeUpdatedEventEvent | TreasuryUpdatedEventEvent | PauseStatusChangedEventEvent | TokenStatusChangedEventEvent;

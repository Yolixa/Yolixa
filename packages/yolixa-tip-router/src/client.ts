import {TipReceipt, SplitRecipient, CreatorStats, ContractEvent} from './types.js';
import {Result, Spec, AssembledTransaction, Client as ContractClient, ClientOptions as ContractClientOptions, MethodOptions} from '@stellar/stellar-sdk/contract';
import {Address, xdr} from '@stellar/stellar-sdk';

export interface Client {
  tip({ sender, creator, token, amount, tip_id }: { sender: string | Address; creator: string | Address; token: string | Address; amount: bigint; tip_id: bigint }, options?: MethodOptions): Promise<AssembledTransaction<Result<null, Error>>>;
  pause({ admin }: { admin: string | Address }, options?: MethodOptions): Promise<AssembledTransaction<Result<null, Error>>>;
  get_tip({ sender, tip_id }: { sender: string | Address; tip_id: bigint }, options?: MethodOptions): Promise<AssembledTransaction<TipReceipt | null>>;
  set_fee({ admin, new_fee_bps }: { admin: string | Address; new_fee_bps: number }, options?: MethodOptions): Promise<AssembledTransaction<Result<null, Error>>>;
  unpause({ admin }: { admin: string | Address }, options?: MethodOptions): Promise<AssembledTransaction<Result<null, Error>>>;
  get_admin(options?: MethodOptions): Promise<AssembledTransaction<Result<string, Error>>>;
  is_paused(options?: MethodOptions): Promise<AssembledTransaction<boolean>>;
  set_token({ admin, token, enabled }: { admin: string | Address; token: string | Address; enabled: boolean }, options?: MethodOptions): Promise<AssembledTransaction<Result<null, Error>>>;
  tip_split({ sender, recipients, token, amount, tip_id }: { sender: string | Address; recipients: Array<SplitRecipient>; token: string | Address; amount: bigint; tip_id: bigint }, options?: MethodOptions): Promise<AssembledTransaction<Result<null, Error>>>;
  initialize({ admin, treasury, fee_bps }: { admin: string | Address; treasury: string | Address; fee_bps: number }, options?: MethodOptions): Promise<AssembledTransaction<Result<null, Error>>>;
  tip_exists({ sender, tip_id }: { sender: string | Address; tip_id: bigint }, options?: MethodOptions): Promise<AssembledTransaction<boolean>>;
  get_fee_bps(options?: MethodOptions): Promise<AssembledTransaction<Result<number, Error>>>;
  get_treasury(options?: MethodOptions): Promise<AssembledTransaction<Result<string, Error>>>;
  set_treasury({ admin, treasury }: { admin: string | Address; treasury: string | Address }, options?: MethodOptions): Promise<AssembledTransaction<Result<null, Error>>>;
  is_token_enabled({ token }: { token: string | Address }, options?: MethodOptions): Promise<AssembledTransaction<boolean>>;
  get_creator_stats({ creator }: { creator: string | Address }, options?: MethodOptions): Promise<AssembledTransaction<CreatorStats>>;
}

export class Client extends ContractClient {
  constructor(public readonly options: ContractClientOptions) {
    super(
      new Spec(["AAAAAAAAAAAAAAADdGlwAAAAAAUAAAAAAAAABnNlbmRlcgAAAAAAEwAAAAAAAAAHY3JlYXRvcgAAAAATAAAAAAAAAAV0b2tlbgAAAAAAABMAAAAAAAAABmFtb3VudAAAAAAACwAAAAAAAAAGdGlwX2lkAAAAAAAGAAAAAQAAA+kAAAACAAAAAw==", "AAAAAAAAAAAAAAAFcGF1c2UAAAAAAAABAAAAAAAAAAVhZG1pbgAAAAAAABMAAAABAAAD6QAAAAIAAAAD", "AAAAAAAAAAAAAAAHZ2V0X3RpcAAAAAACAAAAAAAAAAZzZW5kZXIAAAAAABMAAAAAAAAABnRpcF9pZAAAAAAABgAAAAEAAAPoAAAH0AAAAApUaXBSZWNlaXB0AAA=", "AAAAAAAAAAAAAAAHc2V0X2ZlZQAAAAACAAAAAAAAAAVhZG1pbgAAAAAAABMAAAAAAAAAC25ld19mZWVfYnBzAAAAAAQAAAABAAAD6QAAAAIAAAAD", "AAAAAAAAAAAAAAAHdW5wYXVzZQAAAAABAAAAAAAAAAVhZG1pbgAAAAAAABMAAAABAAAD6QAAAAIAAAAD", "AAAAAAAAAAAAAAAJZ2V0X2FkbWluAAAAAAAAAAAAAAEAAAPpAAAAEwAAAAM=", "AAAAAAAAAAAAAAAJaXNfcGF1c2VkAAAAAAAAAAAAAAEAAAAB", "AAAAAAAAAAAAAAAJc2V0X3Rva2VuAAAAAAAAAwAAAAAAAAAFYWRtaW4AAAAAAAATAAAAAAAAAAV0b2tlbgAAAAAAABMAAAAAAAAAB2VuYWJsZWQAAAAAAQAAAAEAAAPpAAAAAgAAAAM=", "AAAAAAAAAAAAAAAJdGlwX3NwbGl0AAAAAAAABQAAAAAAAAAGc2VuZGVyAAAAAAATAAAAAAAAAApyZWNpcGllbnRzAAAAAAPqAAAH0AAAAA5TcGxpdFJlY2lwaWVudAAAAAAAAAAAAAV0b2tlbgAAAAAAABMAAAAAAAAABmFtb3VudAAAAAAACwAAAAAAAAAGdGlwX2lkAAAAAAAGAAAAAQAAA+kAAAACAAAAAw==", "AAAAAAAAAAAAAAAKaW5pdGlhbGl6ZQAAAAAAAwAAAAAAAAAFYWRtaW4AAAAAAAATAAAAAAAAAAh0cmVhc3VyeQAAABMAAAAAAAAAB2ZlZV9icHMAAAAABAAAAAEAAAPpAAAAAgAAAAM=", "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"]),
      options
    );
  }

   static deploy<T = Client>(options: MethodOptions & Omit<ContractClientOptions, 'contractId'> & { wasmHash: Buffer | string; salt?: Buffer | Uint8Array; format?: "hex" | "base64"; address?: string; }): Promise<AssembledTransaction<T>> {
    return ContractClient.deploy(null, options);
  }
  public readonly fromJSON = {
    tip : this.txFromJSON<Result<null, Error>>,  pause : this.txFromJSON<Result<null, Error>>,  get_tip : this.txFromJSON<TipReceipt | null>,  set_fee : this.txFromJSON<Result<null, Error>>,  unpause : this.txFromJSON<Result<null, Error>>,  get_admin : this.txFromJSON<Result<string, Error>>,  is_paused : this.txFromJSON<boolean>,  set_token : this.txFromJSON<Result<null, Error>>,  tip_split : this.txFromJSON<Result<null, Error>>,  initialize : this.txFromJSON<Result<null, Error>>,  tip_exists : this.txFromJSON<boolean>,  get_fee_bps : this.txFromJSON<Result<number, Error>>,  get_treasury : this.txFromJSON<Result<string, Error>>,  set_treasury : this.txFromJSON<Result<null, Error>>,  is_token_enabled : this.txFromJSON<boolean>,  get_creator_stats : this.txFromJSON<CreatorStats>
  };

  /**
   * Parse a raw contract event (topics + data) into a typed {@link ContractEvent}.
   */
  parseEvent(topics: xdr.ScVal[] | string[], data: xdr.ScVal | string): ContractEvent | undefined {
    return this.spec.parseEvent(topics, data) as ContractEvent | undefined;
  }
  /**
   * Build a topics filter row for the "TipEvent" event, for use in `Api.EventFilter.topics` when calling `server.getEvents`. Omitted fields match any value.
   */
  tipEventEventFilter(topicValues?: { tip_id?: bigint; sender?: string | Address; creator?: string | Address }): string[] {
    return this.spec.eventTopicFilter("TipEvent", topicValues);
  }
  /**
   * Build a topics filter row for the "SplitTipEvent" event, for use in `Api.EventFilter.topics` when calling `server.getEvents`. Omitted fields match any value.
   */
  splitTipEventEventFilter(topicValues?: { tip_id?: bigint; sender?: string | Address }): string[] {
    return this.spec.eventTopicFilter("SplitTipEvent", topicValues);
  }
  /**
   * Build a topics filter row for the "FeeUpdatedEvent" event, for use in `Api.EventFilter.topics` when calling `server.getEvents`. Omitted fields match any value.
   */
  feeUpdatedEventEventFilter(topicValues?: { admin?: string | Address }): string[] {
    return this.spec.eventTopicFilter("FeeUpdatedEvent", topicValues);
  }
  /**
   * Build a topics filter row for the "TreasuryUpdatedEvent" event, for use in `Api.EventFilter.topics` when calling `server.getEvents`. Omitted fields match any value.
   */
  treasuryUpdatedEventEventFilter(topicValues?: { admin?: string | Address }): string[] {
    return this.spec.eventTopicFilter("TreasuryUpdatedEvent", topicValues);
  }
  /**
   * Build a topics filter row for the "PauseStatusChangedEvent" event, for use in `Api.EventFilter.topics` when calling `server.getEvents`. Omitted fields match any value.
   */
  pauseStatusChangedEventEventFilter(topicValues?: { admin?: string | Address }): string[] {
    return this.spec.eventTopicFilter("PauseStatusChangedEvent", topicValues);
  }
  /**
   * Build a topics filter row for the "TokenStatusChangedEvent" event, for use in `Api.EventFilter.topics` when calling `server.getEvents`. Omitted fields match any value.
   */
  tokenStatusChangedEventEventFilter(topicValues?: { admin?: string | Address; token?: string | Address }): string[] {
    return this.spec.eventTopicFilter("TokenStatusChangedEvent", topicValues);
  }
}
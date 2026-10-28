# Domain Glossary (Optional)

This glossary is retained from the original skills document. Load it only when the repository actually works on **InmoGestión** or the concepts listed below; it is not a general PHP/CodeIgniter rule.

| Concept | Code identifier | Suggested table / entity |
|---|---|---|
| User / Person | `User` | `users` / `UserModel` |
| Property / Real estate | `Property` | `properties` / `PropertyModel` |
| Rental agreement | `Contract` / `Lease` | `contracts` / `ContractModel` |
| Rental installment | `Installment` | `installments` / `InstallmentModel` |
| Payment / Receipt | `Payment` / `Receipt` | `payments` / `PaymentModel` |
| Owner settlement | `Settlement` | `settlements` / `SettlementModel` |
| Notification / Alert | `Notification` | `notifications` / `NotificationModel` |
| Roles | `admin`, `owner`, `tenant` | `roles.name` |

This table is illustrative. Verify the real schema and the project's actual ubiquitous language before creating or renaming identifiers.

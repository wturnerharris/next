## Pseudocode

#### Application Lifecycle

1. Load App (startup screen)
2. Load Main Activity View
3. Load Dedicated Line View
3. Toggle Cardinality

#### Application Code
1. Get 10 nearest stations
2. Get next available times per station for a single direction
  - Get associated service_id from calendar per day of week.
  - Get stop times for associated trip_id (using partial service_id) and stop_id
3. Get next times per train
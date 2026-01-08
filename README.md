# ADISE25_2021168
This is an API for card game Ξερή and it supports 2 players with token authentication, full game flow-rules, DeadLock detection, player turn detection and the mysql database is stored in users.iee.ihu.gr. 

## Game flow
1. Players join.
2. Cards are dealt.
3. Players see their cards.
4. Players play their cards and if the game rules are triggered, updates on the score and card location are made. The game detects captures of table cards with same card value or with Jack, also detects Ξερή with same card value adding 10 points and Ξερή with Jack adding 20 points to player's score while the game is still on.
5. If players don't have any cards left, a check of sufficient amount of deck cards is made and new cards are dealt to players.
6. If there are not any deck cards left, it means the game is finished and the remaining cards on the table are parsed to the last player.
7. When the game is finished the final points, of amount of cards and other specific rules, are counted and added to their previous number from making Ξερή. Then, the scores and the winner are displayed.

## Game endpoints
### Reset all
Resets all tables 
```bash
curl "https://users.iee.ihu.gr/~iee2021168/ADISE25_2021168/xeri.php?action=reset"
```

### Create player
Updates table players with username and returns side of player (PLAYER1 OR PLAYER2) and his token.
```bash
curl -X PUT "https://users.iee.ihu.gr/~iee2021168/ADISE25_2021168/xeri.php?action=player" -H "Content-Type: application/json" -d "{\"username\":\"Bob\"}"
```

### Start game by dealing cards
Deals 4 cards to the table and 6 cards to each player.
```bash
curl -X POST "https://users.iee.ihu.gr/~iee2021168/ADISE25_2021168/xeri.php?action=deal"
```

### Show player's and table's cards
Returns the last card on the table and the player cards. The player needs to provide his token.
```bash
curl "https://users.iee.ihu.gr/~iee2021168/ADISE25_2021168/xeri.php?action=show_player_table&token=12345"
```

### Play card
Player needs to provide his token and the card_id from the cards he has.
```bash
curl -X POST "https://users.iee.ihu.gr/~iee2021168/ADISE25_2021168/xeri.php?action=play_card" -H "Content-Type: application/json" -d "{\"token\":\"12345\",\"card_id\":12345}"
```

### Show all players
Returns the tokens of both players. This endpoint is used for development.
```bash
curl "https://users.iee.ihu.gr/~iee2021168/ADISE25_2021168/xeri.php?action=players"
```

### Game status
Returns the status of game, whose player turn it is , current player scores and last action timestamp. 
```bash
curl "https://users.iee.ihu.gr/~iee2021168/ADISE25_2021168/xeri.php?action=status"
```

## Database structure

### Players
* Username - Primary key
* Side (PLAYER1||PLAYER2)
* Token
* Last action timestamp

### Cards
* Id - Primary key
* Suit
* Card value
* Location (TABLE||DECK||PLAYER1||PLAYER2||CAPTURED1||CAPTURED2)
* Order (of cards played in table)

### Game status
* Status (Not active||Initialized||Started||Ended||Aborted)
* Player turn
* Player1 score
* Player2 score
* Last action timestamp

### Stored procedures
* reset_game   -> resets all tables
* shuffled4    -> returns 4 random cards located in deck
* shuffled6    -> returns 6 random cards located in deck
* player_cards -> returns all cards of token related player
* points_count -> updates the score of players according to the game's rules












# ADISE25_2021168

##Database structure
###Players
*Username - Primary key
*Side (PLAYER1||PLAYER2)
*Token
*Last action

###Cards
*Id - Primary key
*Suit
*Card value
*Location (TABLE||DECK||PLAYER1||PLAYER2||CAPTURED1||CAPTURED2)
*Order (of cards played in table)

###Game status
*Status (Not active||Initialized||Started||Ended||Aborted)
*Player turn
*Player1 score
*Player2 score
*Last action

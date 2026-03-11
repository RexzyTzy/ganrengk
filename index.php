<?php
// ============================================================
// DRAGON REALM RPG - Complete PHP RPG Game
// ============================================================
session_start();

// Database Configuration
define('DB_HOST', 'sql.freedb.tech');
define('DB_PORT', 3306);
define('DB_USER', 'freedb_ikannn');
define('DB_PASS', '#F&#ZkWAg8wpdBT');
define('DB_NAME', 'freedb_RenzyTzy');

// Connect to DB
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            return null;
        }
    }
    return $pdo;
}

// Initialize Database Tables
function initDB() {
    $db = getDB();
    if (!$db) return false;
    $db->exec("CREATE TABLE IF NOT EXISTS players (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(32) NOT NULL,
        class VARCHAR(20) DEFAULT 'warrior',
        level INT DEFAULT 1,
        exp INT DEFAULT 0,
        exp_next INT DEFAULT 100,
        hp INT DEFAULT 100,
        max_hp INT DEFAULT 100,
        mp INT DEFAULT 50,
        max_mp INT DEFAULT 50,
        attack INT DEFAULT 15,
        defense INT DEFAULT 10,
        speed INT DEFAULT 10,
        magic INT DEFAULT 5,
        gold INT DEFAULT 100,
        gems INT DEFAULT 0,
        kills INT DEFAULT 0,
        deaths INT DEFAULT 0,
        quests_done INT DEFAULT 0,
        dungeon_floor INT DEFAULT 1,
        location VARCHAR(50) DEFAULT 'town',
        wins INT DEFAULT 0,
        losses INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS inventory (
        id INT AUTO_INCREMENT PRIMARY KEY,
        player_id INT NOT NULL,
        item_name VARCHAR(100) NOT NULL,
        item_type VARCHAR(30) NOT NULL,
        item_rarity VARCHAR(20) DEFAULT 'common',
        item_value INT DEFAULT 10,
        item_effect VARCHAR(200) DEFAULT '',
        quantity INT DEFAULT 1,
        equipped TINYINT DEFAULT 0,
        FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS battle_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        player_id INT NOT NULL,
        enemy_name VARCHAR(100),
        result VARCHAR(20),
        exp_gained INT DEFAULT 0,
        gold_gained INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS quest_progress (
        id INT AUTO_INCREMENT PRIMARY KEY,
        player_id INT NOT NULL,
        quest_id INT NOT NULL,
        status VARCHAR(20) DEFAULT 'active',
        progress INT DEFAULT 0,
        completed_at TIMESTAMP NULL,
        FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS skills (
        id INT AUTO_INCREMENT PRIMARY KEY,
        player_id INT NOT NULL,
        skill_name VARCHAR(100) NOT NULL,
        skill_level INT DEFAULT 1,
        FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE
    )");
    return true;
}

initDB();

// ---- Game Data ----
$CLASSES = [
    'warrior'  => ['name'=>'Warrior','hp'=>150,'mp'=>30,'atk'=>20,'def'=>15,'spd'=>10,'mag'=>3,'color'=>'#e74c3c','icon'=>'⚔️','desc'=>'Tanky melee fighter with high defense'],
    'mage'     => ['name'=>'Mage',   'hp'=>70, 'mp'=>120,'atk'=>8, 'def'=>5, 'spd'=>12,'mag'=>25,'color'=>'#9b59b6','icon'=>'🔮','desc'=>'Powerful spellcaster with devastating magic'],
    'rogue'    => ['name'=>'Rogue',  'hp'=>90, 'mp'=>50, 'atk'=>18,'def'=>8, 'spd'=>22,'mag'=>8, 'color'=>'#2ecc71','icon'=>'🗡️','desc'=>'Fast assassin with critical strike power'],
    'paladin'  => ['name'=>'Paladin','hp'=>130,'mp'=>80, 'atk'=>15,'def'=>18,'spd'=>8, 'mag'=>15,'color'=>'#f39c12','icon'=>'🛡️','desc'=>'Holy knight blending sword and divine magic'],
    'ranger'   => ['name'=>'Ranger', 'hp'=>100,'mp'=>60, 'atk'=>17,'def'=>9, 'spd'=>18,'mag'=>10,'color'=>'#27ae60','icon'=>'🏹','desc'=>'Nimble archer who attacks from afar'],
];

$MONSTERS = [
    ['name'=>'Slime','hp'=>30,'atk'=>5,'def'=>2,'exp'=>20,'gold'=>10,'icon'=>'🟢','level'=>1,'desc'=>'A gelatinous blob'],
    ['name'=>'Goblin','hp'=>50,'atk'=>10,'def'=>5,'exp'=>40,'gold'=>20,'icon'=>'👺','level'=>1,'desc'=>'Sneaky little menace'],
    ['name'=>'Wolf','hp'=>70,'atk'=>15,'def'=>8,'exp'=>60,'gold'=>25,'icon'=>'🐺','level'=>2,'desc'=>'Pack hunter'],
    ['name'=>'Skeleton','hp'=>80,'atk'=>18,'def'=>12,'exp'=>80,'gold'=>35,'icon'=>'💀','level'=>2,'desc'=>'Risen from the dead'],
    ['name'=>'Orc','hp'=>120,'atk'=>22,'def'=>15,'exp'=>100,'gold'=>50,'icon'=>'👹','level'=>3,'desc'=>'Brutish warrior'],
    ['name'=>'Troll','hp'=>180,'atk'=>28,'def'=>20,'exp'=>150,'gold'=>75,'icon'=>'🧌','level'=>4,'desc'=>'Regenerating giant'],
    ['name'=>'Dark Knight','hp'=>250,'atk'=>35,'def'=>28,'exp'=>250,'gold'=>120,'icon'=>'🖤','level'=>6,'desc'=>'Fallen champion'],
    ['name'=>'Dragon Whelp','hp'=>350,'atk'=>45,'def'=>35,'exp'=>400,'gold'=>200,'icon'=>'🐉','level'=>8,'desc'=>'Young but deadly'],
    ['name'=>'Lich','hp'=>500,'atk'=>60,'def'=>40,'exp'=>700,'gold'=>350,'icon'=>'🧟','level'=>12,'desc'=>'Undead sorcerer'],
    ['name'=>'Ancient Dragon','hp'=>1000,'atk'=>90,'def'=>70,'exp'=>2000,'gold'=>1000,'icon'=>'🔥','level'=>20,'desc'=>'BOSS - The Final Challenge'],
];

$ITEMS_SHOP = [
    ['name'=>'Health Potion','type'=>'consumable','rarity'=>'common','value'=>50,'effect'=>'Restore 50 HP','icon'=>'🧪'],
    ['name'=>'Mega Potion','type'=>'consumable','rarity'=>'uncommon','value'=>150,'effect'=>'Restore 150 HP','icon'=>'💊'],
    ['name'=>'Mana Elixir','type'=>'consumable','rarity'=>'common','value'=>60,'effect'=>'Restore 50 MP','icon'=>'💧'],
    ['name'=>'Antidote','type'=>'consumable','rarity'=>'common','value'=>30,'effect'=>'Cure poison','icon'=>'🍃'],
    ['name'=>'Iron Sword','type'=>'weapon','rarity'=>'common','value'=>200,'effect'=>'+10 ATK','icon'=>'⚔️'],
    ['name'=>'Steel Sword','type'=>'weapon','rarity'=>'uncommon','value'=>500,'effect'=>'+20 ATK','icon'=>'🗡️'],
    ['name'=>'Mithril Blade','type'=>'weapon','rarity'=>'rare','value'=>1500,'effect'=>'+35 ATK','icon'=>'✨'],
    ['name'=>'Iron Shield','type'=>'armor','rarity'=>'common','value'=>180,'effect'=>'+8 DEF','icon'=>'🛡️'],
    ['name'=>'Chain Mail','type'=>'armor','rarity'=>'uncommon','value'=>450,'effect'=>'+18 DEF','icon'=>'🪖'],
    ['name'=>'Dragon Armor','type'=>'armor','rarity'=>'epic','value'=>3000,'effect'=>'+50 DEF','icon'=>'🐉'],
    ['name'=>'Speed Boots','type'=>'accessory','rarity'=>'uncommon','value'=>400,'effect'=>'+10 SPD','icon'=>'👟'],
    ['name'=>'Magic Ring','type'=>'accessory','rarity'=>'rare','value'=>800,'effect'=>'+15 MAG','icon'=>'💍'],
    ['name'=>'Phoenix Down','type'=>'consumable','rarity'=>'rare','value'=>500,'effect'=>'Revive with 50% HP','icon'=>'🪶'],
    ['name'=>'Elixir','type'=>'consumable','rarity'=>'epic','value'=>2000,'effect'=>'Full HP & MP restore','icon'=>'⚗️'],
];

$SKILLS_DATA = [
    'warrior' => [
        ['name'=>'Slash','mp'=>5,'dmg_mult'=>1.5,'desc'=>'A powerful slash attack','icon'=>'⚔️'],
        ['name'=>'Shield Bash','mp'=>8,'dmg_mult'=>1.2,'desc'=>'Stun enemy with shield','icon'=>'🛡️'],
        ['name'=>'Berserker Rage','mp'=>15,'dmg_mult'=>2.5,'desc'=>'Enter rage, massive damage','icon'=>'💢'],
        ['name'=>'War Cry','mp'=>10,'dmg_mult'=>1.0,'desc'=>'Boost your own attack','icon'=>'📣'],
    ],
    'mage' => [
        ['name'=>'Fireball','mp'=>15,'dmg_mult'=>2.0,'desc'=>'Hurls a ball of fire','icon'=>'🔥'],
        ['name'=>'Ice Lance','mp'=>12,'dmg_mult'=>1.8,'desc'=>'Sharp icy projectile','icon'=>'❄️'],
        ['name'=>'Thunder','mp'=>20,'dmg_mult'=>2.5,'desc'=>'Lightning from the sky','icon'=>'⚡'],
        ['name'=>'Meteor','mp'=>40,'dmg_mult'=>4.0,'desc'=>'Call down a meteor','icon'=>'☄️'],
    ],
    'rogue' => [
        ['name'=>'Backstab','mp'=>8,'dmg_mult'=>2.2,'desc'=>'Strike from shadows','icon'=>'🗡️'],
        ['name'=>'Poison Blade','mp'=>10,'dmg_mult'=>1.5,'desc'=>'Apply deadly poison','icon'=>'☠️'],
        ['name'=>'Smoke Bomb','mp'=>12,'dmg_mult'=>0.5,'desc'=>'Evade next attack','icon'=>'💨'],
        ['name'=>'Death Strike','mp'=>25,'dmg_mult'=>3.5,'desc'=>'Lethal assassination','icon'=>'💀'],
    ],
    'paladin' => [
        ['name'=>'Holy Strike','mp'=>10,'dmg_mult'=>1.8,'desc'=>'Divine-powered swing','icon'=>'✨'],
        ['name'=>'Heal','mp'=>15,'dmg_mult'=>0,'desc'=>'Restore 40 HP','icon'=>'💚'],
        ['name'=>'Divine Shield','mp'=>20,'dmg_mult'=>0,'desc'=>'Become immune briefly','icon'=>'🌟'],
        ['name'=>'Judgment','mp'=>30,'dmg_mult'=>3.0,'desc'=>'Holy divine judgment','icon'=>'⚖️'],
    ],
    'ranger' => [
        ['name'=>'Arrow Shot','mp'=>5,'dmg_mult'=>1.4,'desc'=>'Precise arrow strike','icon'=>'🏹'],
        ['name'=>'Multi-Shot','mp'=>12,'dmg_mult'=>1.2,'desc'=>'Fire 3 arrows at once','icon'=>'🎯'],
        ['name'=>'Trap','mp'=>10,'dmg_mult'=>1.6,'desc'=>'Set a deadly trap','icon'=>'🔩'],
        ['name'=>'Eagle Eye','mp'=>20,'dmg_mult'=>3.0,'desc'=>'Perfect aimed shot','icon'=>'👁️'],
    ],
];

$QUESTS = [
    ['id'=>1,'name'=>'First Blood','desc'=>'Defeat 5 enemies','req_kills'=>5,'reward_exp'=>200,'reward_gold'=>100,'icon'=>'⚔️'],
    ['id'=>2,'name'=>'Slime Hunter','desc'=>'Defeat 10 Slimes','req_kills'=>10,'reward_exp'=>500,'reward_gold'=>250,'icon'=>'🟢'],
    ['id'=>3,'name'=>'Monster Slayer','desc'=>'Defeat 25 enemies','req_kills'=>25,'reward_exp'=>1000,'reward_gold'=>500,'icon'=>'🏆'],
    ['id'=>4,'name'=>'Gold Collector','desc'=>'Accumulate 500 gold','req_kills'=>0,'reward_exp'=>300,'reward_gold'=>0,'icon'=>'💰'],
    ['id'=>5,'name'=>'Dungeon Explorer','desc'=>'Reach Dungeon Floor 5','req_kills'=>0,'reward_exp'=>800,'reward_gold'=>400,'icon'=>'🏰'],
    ['id'=>6,'name'=>'Dragon Slayer','desc'=>'Defeat the Ancient Dragon','req_kills'=>0,'reward_exp'=>5000,'reward_gold'=>2000,'icon'=>'🐉'],
];

// ---- Helper Functions ----
function isLoggedIn() { return isset($_SESSION['player_id']); }

function getPlayer($id = null) {
    $db = getDB();
    if (!$db) return null;
    $id = $id ?? $_SESSION['player_id'];
    $stmt = $db->prepare("SELECT * FROM players WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function updatePlayer($id, $data) {
    $db = getDB();
    if (!$db) return;
    $sets = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
    $stmt = $db->prepare("UPDATE players SET $sets WHERE id = ?");
    $stmt->execute([...array_values($data), $id]);
}

function getInventory($pid) {
    $db = getDB();
    if (!$db) return [];
    $stmt = $db->prepare("SELECT * FROM inventory WHERE player_id = ?");
    $stmt->execute([$pid]);
    return $stmt->fetchAll();
}

function getBattleLog($pid, $limit = 10) {
    $db = getDB();
    if (!$db) return [];
    $stmt = $db->prepare("SELECT * FROM battle_log WHERE player_id = ? ORDER BY created_at DESC LIMIT $limit");
    $stmt->execute([$pid]);
    return $stmt->fetchAll();
}

function getSkills($pid) {
    $db = getDB();
    if (!$db) return [];
    $stmt = $db->prepare("SELECT * FROM skills WHERE player_id = ?");
    $stmt->execute([$pid]);
    return $stmt->fetchAll();
}

function levelUp($player) {
    $db = getDB();
    if (!$db) return $player;
    while ($player['exp'] >= $player['exp_next']) {
        $player['level']++;
        $player['exp'] -= $player['exp_next'];
        $player['exp_next'] = intval($player['exp_next'] * 1.5);
        $player['max_hp'] += 20;
        $player['max_mp'] += 10;
        $player['attack'] += 3;
        $player['defense'] += 2;
        $player['speed'] += 1;
        $player['magic'] += 2;
        $player['hp'] = $player['max_hp'];
        $player['mp'] = $player['max_mp'];
    }
    updatePlayer($player['id'], [
        'level'=>$player['level'],'exp'=>$player['exp'],'exp_next'=>$player['exp_next'],
        'max_hp'=>$player['max_hp'],'max_mp'=>$player['max_mp'],
        'hp'=>$player['hp'],'mp'=>$player['mp'],
        'attack'=>$player['attack'],'defense'=>$player['defense'],
        'speed'=>$player['speed'],'magic'=>$player['magic'],
    ]);
    return $player;
}

function giveStarterItems($pid, $class) {
    $db = getDB();
    if (!$db) return;
    $starters = [
        'warrior' => [['Iron Sword','weapon','common',200,'+10 ATK'],['Health Potion','consumable','common',50,'Restore 50 HP']],
        'mage'    => [['Magic Ring','accessory','rare',800,'+15 MAG'],['Mana Elixir','consumable','common',60,'Restore 50 MP']],
        'rogue'   => [['Speed Boots','accessory','uncommon',400,'+10 SPD'],['Antidote','consumable','common',30,'Cure poison']],
        'paladin' => [['Iron Shield','armor','common',180,'+8 DEF'],['Health Potion','consumable','common',50,'Restore 50 HP']],
        'ranger'  => [['Health Potion','consumable','common',50,'Restore 50 HP'],['Mana Elixir','consumable','common',60,'Restore 50 MP']],
    ];
    foreach (($starters[$class] ?? []) as $item) {
        $stmt = $db->prepare("INSERT INTO inventory (player_id,item_name,item_type,item_rarity,item_value,item_effect) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$pid, $item[0], $item[1], $item[2], $item[3], $item[4]]);
    }
    // Give starter skills
    global $SKILLS_DATA;
    foreach (($SKILLS_DATA[$class] ?? []) as $skill) {
        $stmt = $db->prepare("INSERT INTO skills (player_id, skill_name) VALUES (?,?)");
        $stmt->execute([$pid, $skill['name']]);
    }
}

// ---- Action Handlers ----
$message = '';
$message_type = 'info';
$battle_result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // REGISTER
    if ($action === 'register') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $class = $_POST['class'] ?? 'warrior';
        $db = getDB();
        if (!$db) { $message = '❌ Database connection failed!'; $message_type='error'; }
        elseif (strlen($username) < 3) { $message = '❌ Username must be at least 3 characters!'; $message_type='error'; }
        elseif (strlen($password) < 4) { $message = '❌ Password must be at least 4 characters!'; $message_type='error'; }
        elseif (!isset($CLASSES[$class])) { $message = '❌ Invalid class!'; $message_type='error'; }
        else {
            $stmt = $db->prepare("SELECT id FROM players WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) { $message = '❌ Username already taken!'; $message_type='error'; }
            else {
                $cls = $CLASSES[$class];
                $hash = md5($password);
                $stmt = $db->prepare("INSERT INTO players (username,password,class,hp,max_hp,mp,max_mp,attack,defense,speed,magic) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$username,$hash,$class,$cls['hp'],$cls['hp'],$cls['mp'],$cls['mp'],$cls['atk'],$cls['def'],$cls['spd'],$cls['mag']]);
                $pid = $db->lastInsertId();
                giveStarterItems($pid, $class);
                $message = '✅ Account created! Welcome, '.$username.'!';
                $message_type = 'success';
                $_SESSION['show_login'] = true;
            }
        }
    }

    // LOGIN
    if ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = md5($_POST['password'] ?? '');
        $db = getDB();
        if (!$db) { $message = '❌ Database connection failed!'; $message_type='error'; }
        else {
            $stmt = $db->prepare("SELECT * FROM players WHERE username = ? AND password = ?");
            $stmt->execute([$username, $password]);
            $player = $stmt->fetch();
            if ($player) {
                $_SESSION['player_id'] = $player['id'];
                $message = '✅ Welcome back, '.$player['username'].'!';
                $message_type = 'success';
            } else {
                $message = '❌ Invalid username or password!';
                $message_type = 'error';
            }
        }
    }

    // LOGOUT
    if ($action === 'logout') {
        session_destroy();
        header('Location: '.$_SERVER['PHP_SELF']);
        exit;
    }

    if (isLoggedIn()) {
        $player = getPlayer();
        if (!$player) { session_destroy(); header('Location: '.$_SERVER['PHP_SELF']); exit; }

        // BATTLE
        if ($action === 'battle') {
            $monster_idx = intval($_POST['monster_idx'] ?? 0);
            global $MONSTERS;
            $monster = $MONSTERS[$monster_idx] ?? $MONSTERS[0];
            $player_hp = $player['hp'];
            $player_mp = $player['mp'];
            $monster_hp = $monster['hp'] + ($player['level'] * 5);
            $rounds = [];
            $turn = 0;
            $skill_used = $_POST['skill_used'] ?? '';
            $skill_dmg_mult = 1.0;
            $skill_mp_cost = 0;
            global $SKILLS_DATA;
            foreach (($SKILLS_DATA[$player['class']] ?? []) as $sk) {
                if ($sk['name'] === $skill_used) { $skill_dmg_mult = $sk['dmg_mult']; $skill_mp_cost = $sk['mp']; break; }
            }
            if ($player_mp < $skill_mp_cost) { $skill_used = ''; $skill_dmg_mult = 1.0; $skill_mp_cost = 0; }
            $player_mp -= $skill_mp_cost;
            while ($player_hp > 0 && $monster_hp > 0 && $turn < 20) {
                $turn++;
                $pAtk = max(1, $player['attack'] + rand(-3,3));
                if ($skill_used && $turn === 1) $pAtk = intval($pAtk * $skill_dmg_mult);
                $pDmg = max(1, $pAtk - intval($monster['def'] * 0.5));
                $monster_hp -= $pDmg;
                $skill_note = ($skill_used && $turn===1) ? " [<b>$skill_used</b>]" : '';
                $rounds[] = "<span class='hit-player'>⚔️ You attack{$skill_note}: <b>-{$pDmg} HP</b></span>";
                if ($monster_hp <= 0) break;
                $mAtk = max(1, $monster['atk'] + rand(-2,2));
                $mDmg = max(1, $mAtk - intval($player['defense'] * 0.5));
                $player_hp -= $mDmg;
                $rounds[] = "<span class='hit-enemy'>🔥 {$monster['name']} attacks: <b>-{$mDmg} HP</b></span>";
            }
            $won = $monster_hp <= 0;
            $exp_gain = $won ? $monster['exp'] : 0;
            $gold_gain = $won ? $monster['gold'] + rand(0, 10) : 0;
            $new_hp = max(0, min($player['max_hp'], $player_hp));
            $new_exp = $player['exp'] + $exp_gain;
            $new_gold = $player['gold'] + $gold_gain;
            $new_kills = $player['kills'] + ($won ? 1 : 0);
            $new_deaths = $player['deaths'] + ($won ? 0 : 1);
            if (!$won) $new_hp = intval($player['max_hp'] * 0.3);
            updatePlayer($player['id'], [
                'hp'=>$new_hp,'mp'=>$player_mp,'exp'=>$new_exp,
                'gold'=>$new_gold,'kills'=>$new_kills,'deaths'=>$new_deaths,
                'wins'=>$player['wins']+($won?1:0),'losses'=>$player['losses']+($won?0:1),
            ]);
            $db = getDB();
            $stmt = $db->prepare("INSERT INTO battle_log (player_id,enemy_name,result,exp_gained,gold_gained) VALUES (?,?,?,?,?)");
            $stmt->execute([$player['id'],$monster['name'],$won?'win':'loss',$exp_gain,$gold_gain]);
            $player = levelUp(getPlayer());
            $battle_result = [
                'won'=>$won,'monster'=>$monster,'rounds'=>$rounds,
                'exp_gain'=>$exp_gain,'gold_gain'=>$gold_gain,'player'=>$player,
            ];
            $message = $won ? "🏆 Victory! Defeated {$monster['name']}! +{$exp_gain} EXP, +{$gold_gain} Gold" : "💀 Defeated... You lost to {$monster['name']}...";
            $message_type = $won ? 'success' : 'error';
        }

        // USE ITEM
        if ($action === 'use_item') {
            $item_id = intval($_POST['item_id'] ?? 0);
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM inventory WHERE id = ? AND player_id = ?");
            $stmt->execute([$item_id, $player['id']]);
            $item = $stmt->fetch();
            if ($item) {
                $updates = [];
                if (stripos($item['item_effect'], 'HP') !== false) {
                    preg_match('/(\d+)/', $item['item_effect'], $m);
                    $amount = intval($m[1] ?? 50);
                    if (stripos($item['item_effect'], 'Full') !== false) $amount = $player['max_hp'];
                    $updates['hp'] = min($player['max_hp'], $player['hp'] + $amount);
                    $message = "💚 Used {$item['item_name']}! Restored {$amount} HP."; $message_type='success';
                }
                if (stripos($item['item_effect'], 'MP') !== false) {
                    preg_match('/(\d+)/', $item['item_effect'], $m);
                    $amount = intval($m[1] ?? 50);
                    if (stripos($item['item_effect'], 'Full') !== false) $amount = $player['max_mp'];
                    $updates['mp'] = min($player['max_mp'], $player['mp'] + $amount);
                    $message .= " 💧 Restored {$amount} MP."; $message_type='success';
                }
                if ($updates) updatePlayer($player['id'], $updates);
                if ($item['quantity'] <= 1) {
                    $db->prepare("DELETE FROM inventory WHERE id = ?")->execute([$item_id]);
                } else {
                    $db->prepare("UPDATE inventory SET quantity = quantity - 1 WHERE id = ?")->execute([$item_id]);
                }
                if (!$message) { $message = "Used {$item['item_name']}!"; $message_type='info'; }
            }
        }

        // BUY ITEM
        if ($action === 'buy_item') {
            global $ITEMS_SHOP;
            $idx = intval($_POST['item_idx'] ?? -1);
            $item = $ITEMS_SHOP[$idx] ?? null;
            if ($item && $player['gold'] >= $item['value']) {
                updatePlayer($player['id'], ['gold' => $player['gold'] - $item['value']]);
                $db = getDB();
                $stmt = $db->prepare("SELECT id FROM inventory WHERE player_id = ? AND item_name = ?");
                $stmt->execute([$player['id'], $item['name']]);
                $existing = $stmt->fetch();
                if ($existing && $item['type'] === 'consumable') {
                    $db->prepare("UPDATE inventory SET quantity = quantity + 1 WHERE id = ?")->execute([$existing['id']]);
                } else {
                    $stmt = $db->prepare("INSERT INTO inventory (player_id,item_name,item_type,item_rarity,item_value,item_effect) VALUES (?,?,?,?,?,?)");
                    $stmt->execute([$player['id'],$item['name'],$item['type'],$item['rarity'],$item['value'],$item['effect']]);
                }
                $message = "🛒 Bought {$item['name']} for {$item['value']} gold!"; $message_type='success';
            } else {
                $message = "❌ Not enough gold!"; $message_type='error';
            }
        }

        // SELL ITEM
        if ($action === 'sell_item') {
            $item_id = intval($_POST['item_id'] ?? 0);
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM inventory WHERE id = ? AND player_id = ?");
            $stmt->execute([$item_id, $player['id']]);
            $item = $stmt->fetch();
            if ($item) {
                $sell_price = intval($item['item_value'] * 0.5);
                updatePlayer($player['id'], ['gold' => $player['gold'] + $sell_price]);
                $db->prepare("DELETE FROM inventory WHERE id = ?")->execute([$item_id]);
                $message = "💰 Sold {$item['item_name']} for {$sell_price} gold!"; $message_type='success';
            }
        }

        // EQUIP ITEM
        if ($action === 'equip_item') {
            $item_id = intval($_POST['item_id'] ?? 0);
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM inventory WHERE id = ? AND player_id = ?");
            $stmt->execute([$item_id, $player['id']]);
            $item = $stmt->fetch();
            if ($item && in_array($item['item_type'], ['weapon','armor','accessory'])) {
                $db->prepare("UPDATE inventory SET equipped = 0 WHERE player_id = ? AND item_type = ?")->execute([$player['id'], $item['item_type']]);
                $db->prepare("UPDATE inventory SET equipped = 1 WHERE id = ?")->execute([$item_id]);
                // Apply stat from item
                preg_match('/\+(\d+)\s+(\w+)/', $item['item_effect'], $m);
                if ($m) {
                    $bonus = intval($m[1]);
                    $stat = strtolower($m[2]);
                    $map = ['atk'=>'attack','def'=>'defense','spd'=>'speed','mag'=>'magic','hp'=>'max_hp','mp'=>'max_mp'];
                    $col = $map[$stat] ?? null;
                    if ($col) updatePlayer($player['id'], [$col => $player[$col] + $bonus]);
                }
                $message = "✅ Equipped {$item['item_name']}!"; $message_type='success';
            }
        }

        // HEAL AT INN
        if ($action === 'inn_heal') {
            $cost = 50;
            if ($player['gold'] >= $cost) {
                updatePlayer($player['id'], ['hp'=>$player['max_hp'],'mp'=>$player['max_mp'],'gold'=>$player['gold']-$cost]);
                $message = "🏠 Fully rested at the Inn! (-{$cost} gold)"; $message_type='success';
            } else { $message = "❌ Not enough gold for the Inn (costs {$cost} gold)!"; $message_type='error'; }
        }

        // DUNGEON
        if ($action === 'enter_dungeon') {
            $floor = $player['dungeon_floor'];
            $monster_idx = min(count($MONSTERS)-1, intval($floor/3));
            $monster = $MONSTERS[$monster_idx];
            $monster['hp'] = intval($monster['hp'] * (1 + $floor * 0.2));
            $monster['atk'] = intval($monster['atk'] * (1 + $floor * 0.1));
            $player_hp = $player['hp'];
            $won = false;
            $rounds = [];
            $monster_hp = $monster['hp'];
            for ($t = 0; $t < 20 && $player_hp > 0 && $monster_hp > 0; $t++) {
                $pDmg = max(1, $player['attack'] + rand(-3,3) - intval($monster['def']*0.5));
                $monster_hp -= $pDmg;
                $rounds[] = "<span class='hit-player'>⚔️ Attack: <b>-{$pDmg} HP</b></span>";
                if ($monster_hp <= 0) { $won = true; break; }
                $mDmg = max(1, $monster['atk'] + rand(-2,2) - intval($player['defense']*0.5));
                $player_hp -= $mDmg;
                $rounds[] = "<span class='hit-enemy'>🔥 {$monster['name']}: <b>-{$mDmg} HP</b></span>";
            }
            $new_floor = $won ? $floor + 1 : max(1, $floor - 1);
            $exp_gain = $won ? $monster['exp'] + $floor * 20 : 0;
            $gold_gain = $won ? $monster['gold'] + $floor * 10 : 0;
            $new_hp = max(1, min($player['max_hp'], $player_hp));
            if (!$won) $new_hp = intval($player['max_hp'] * 0.2);
            updatePlayer($player['id'], [
                'hp'=>$new_hp,'exp'=>$player['exp']+$exp_gain,'gold'=>$player['gold']+$gold_gain,
                'dungeon_floor'=>$new_floor,'kills'=>$player['kills']+($won?1:0),
            ]);
            $db = getDB();
            $stmt = $db->prepare("INSERT INTO battle_log (player_id,enemy_name,result,exp_gained,gold_gained) VALUES (?,?,?,?,?)");
            $stmt->execute([$player['id'],"[Dungeon F{$floor}] ".$monster['name'],$won?'win':'loss',$exp_gain,$gold_gain]);
            $player = levelUp(getPlayer());
            $battle_result = ['won'=>$won,'monster'=>$monster,'rounds'=>$rounds,'exp_gain'=>$exp_gain,'gold_gain'=>$gold_gain,'player'=>$player,'dungeon'=>true,'floor'=>$floor];
            $message = $won ? "🏰 Floor {$floor} cleared! Advanced to Floor {$new_floor}!" : "💀 Defeated on Floor {$floor}...";
            $message_type = $won ? 'success' : 'error';
        }

        // REFRESH PLAYER
        $player = getPlayer();
    }
}

$player = isLoggedIn() ? getPlayer() : null;
$inventory = $player ? getInventory($player['id']) : [];
$battle_log = $player ? getBattleLog($player['id']) : [];
$skills = $player ? getSkills($player['id']) : [];

// Leaderboard
function getLeaderboard() {
    $db = getDB();
    if (!$db) return [];
    $stmt = $db->query("SELECT username,class,level,kills,gold FROM players ORDER BY level DESC, kills DESC LIMIT 10");
    return $stmt->fetchAll();
}
$leaderboard = getLeaderboard();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>⚔️ Dragon Realm RPG</title>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700;900&family=Crimson+Text:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
<style>
:root {
    --gold: #d4af37;
    --gold-light: #f0d060;
    --dark: #0a0805;
    --dark2: #12100a;
    --dark3: #1e1a10;
    --dark4: #2a2416;
    --dark5: #352e1c;
    --red: #c0392b;
    --red-glow: #e74c3c;
    --blue: #2980b9;
    --green: #27ae60;
    --purple: #8e44ad;
    --orange: #e67e22;
    --text: #e8d5a3;
    --text-dim: #a09060;
    --border: #4a3e20;
    --rarity-common: #aaa;
    --rarity-uncommon: #4af542;
    --rarity-rare: #4090ff;
    --rarity-epic: #cc44ff;
    --rarity-legendary: #ffaa00;
}
* { margin:0;padding:0;box-sizing:border-box; }
html { scroll-behavior:smooth; }
body {
    background: var(--dark);
    color: var(--text);
    font-family: 'Crimson Text', serif;
    font-size: 17px;
    min-height: 100vh;
    background-image:
        radial-gradient(ellipse at 20% 50%, rgba(180,130,0,0.05) 0%, transparent 60%),
        radial-gradient(ellipse at 80% 20%, rgba(120,0,0,0.07) 0%, transparent 50%);
    overflow-x: hidden;
}

/* === PARTICLES === */
.particles { position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:0; }
.particle {
    position:absolute;border-radius:50%;opacity:0;
    animation: float-up linear infinite;
}
@keyframes float-up {
    0%{transform:translateY(100vh) scale(0);opacity:0}
    10%{opacity:1}
    90%{opacity:0.5}
    100%{transform:translateY(-20px) scale(1);opacity:0}
}

/* === HEADER === */
header {
    background: linear-gradient(180deg, #000 0%, var(--dark2) 100%);
    border-bottom: 2px solid var(--gold);
    padding: 12px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 4px 30px rgba(212,175,55,0.2);
}
.logo {
    font-family: 'Cinzel', serif;
    font-size: 1.8rem;
    font-weight: 900;
    color: var(--gold);
    text-shadow: 0 0 20px rgba(212,175,55,0.8), 0 0 40px rgba(212,175,55,0.3);
    letter-spacing: 3px;
    animation: logo-pulse 3s ease-in-out infinite;
}
@keyframes logo-pulse {
    0%,100%{text-shadow:0 0 20px rgba(212,175,55,0.8),0 0 40px rgba(212,175,55,0.3)}
    50%{text-shadow:0 0 30px rgba(212,175,55,1),0 0 60px rgba(212,175,55,0.5),0 0 80px rgba(212,175,55,0.2)}
}
.logo span { color: var(--red-glow); }

/* === TABS === */
.nav-tabs {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
}
.tab-btn {
    background: var(--dark3);
    border: 1px solid var(--border);
    color: var(--text-dim);
    padding: 8px 14px;
    cursor: pointer;
    font-family: 'Cinzel', serif;
    font-size: 0.75rem;
    letter-spacing: 1px;
    transition: all 0.2s;
    border-radius: 3px;
    text-transform: uppercase;
}
.tab-btn:hover, .tab-btn.active {
    background: var(--dark5);
    border-color: var(--gold);
    color: var(--gold);
    box-shadow: 0 0 10px rgba(212,175,55,0.3);
}

/* === PLAYER BAR === */
.player-bar {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 0.85rem;
}
.player-avatar {
    width: 40px;
    height: 40px;
    background: var(--dark4);
    border: 2px solid var(--gold);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    box-shadow: 0 0 15px rgba(212,175,55,0.4);
}
.player-name { font-family:'Cinzel',serif; color:var(--gold); font-weight:700; }
.player-stats-mini { color:var(--text-dim); font-size:0.8rem; }

/* === MAIN LAYOUT === */
.main-wrap {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
    position: relative;
    z-index: 1;
}
.game-grid {
    display: grid;
    grid-template-columns: 280px 1fr 280px;
    gap: 16px;
    align-items: start;
}
@media(max-width:1100px){.game-grid{grid-template-columns:1fr;}}

/* === PANELS === */
.panel {
    background: linear-gradient(135deg, var(--dark2) 0%, var(--dark3) 100%);
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 16px;
    position: relative;
    overflow: hidden;
}
.panel::before {
    content:'';position:absolute;top:0;left:0;right:0;height:2px;
    background:linear-gradient(90deg,transparent,var(--gold),transparent);
}
.panel-title {
    font-family: 'Cinzel', serif;
    font-size: 0.9rem;
    color: var(--gold);
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 8px;
}

/* === TABS CONTENT === */
.tab-content { display: none; }
.tab-content.active { display: block; }

/* === STAT BARS === */
.stat-bar-wrap { margin-bottom: 8px; }
.stat-label { display:flex;justify-content:space-between;font-size:0.8rem;margin-bottom:3px; }
.stat-bar {
    height: 10px;
    background: var(--dark4);
    border-radius: 5px;
    overflow: hidden;
    border: 1px solid var(--dark5);
}
.stat-bar-fill {
    height: 100%;
    border-radius: 5px;
    transition: width 0.5s ease;
    position: relative;
    overflow: hidden;
}
.stat-bar-fill::after {
    content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,0.3),transparent);
    animation:shimmer 2s infinite;
}
@keyframes shimmer{0%{left:-100%}100%{left:100%}}
.hp-bar { background: linear-gradient(90deg, #c0392b, #e74c3c); }
.mp-bar { background: linear-gradient(90deg, #1a6891, #2980b9); }
.exp-bar { background: linear-gradient(90deg, #8e6914, #d4af37); }

/* === STATS GRID === */
.stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-top: 12px;
}
.stat-item {
    background: var(--dark4);
    border: 1px solid var(--border);
    border-radius: 4px;
    padding: 8px;
    text-align: center;
}
.stat-item .val { font-size:1.2rem;font-weight:700;color:var(--gold-light); }
.stat-item .lbl { font-size:0.7rem;color:var(--text-dim);text-transform:uppercase;letter-spacing:1px; }

/* === BUTTONS === */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 16px;
    border: 1px solid;
    border-radius: 4px;
    cursor: pointer;
    font-family: 'Cinzel', serif;
    font-size: 0.8rem;
    letter-spacing: 1px;
    text-transform: uppercase;
    transition: all 0.2s;
    text-decoration: none;
    background: none;
}
.btn:hover { transform: translateY(-1px); box-shadow: 0 4px 15px rgba(0,0,0,0.4); }
.btn:active { transform: translateY(0); }
.btn-gold { border-color:var(--gold);color:var(--gold);background:rgba(212,175,55,0.1); }
.btn-gold:hover { background:rgba(212,175,55,0.25);box-shadow:0 4px 15px rgba(212,175,55,0.3); }
.btn-red { border-color:var(--red-glow);color:var(--red-glow);background:rgba(231,76,60,0.1); }
.btn-red:hover { background:rgba(231,76,60,0.25); }
.btn-green { border-color:var(--green);color:var(--green);background:rgba(39,174,96,0.1); }
.btn-green:hover { background:rgba(39,174,96,0.25); }
.btn-blue { border-color:var(--blue);color:var(--blue);background:rgba(41,128,185,0.1); }
.btn-blue:hover { background:rgba(41,128,185,0.25); }
.btn-purple { border-color:var(--purple);color:var(--purple);background:rgba(142,68,173,0.1); }
.btn-purple:hover { background:rgba(142,68,173,0.25); }
.btn-full { width:100%;margin-bottom:6px; }
.btn-sm { padding:5px 10px;font-size:0.7rem; }

/* === MONSTER CARD === */
.monster-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill,minmax(180px,1fr));
    gap: 10px;
}
.monster-card {
    background: var(--dark3);
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 12px;
    text-align: center;
    transition: all 0.2s;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}
.monster-card:hover {
    border-color: var(--gold);
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.5);
}
.monster-card::before {
    content:'';position:absolute;inset:0;
    background:linear-gradient(135deg,rgba(212,175,55,0.05),transparent);
    opacity:0;transition:opacity 0.2s;
}
.monster-card:hover::before{opacity:1}
.monster-icon { font-size: 2.5rem; margin-bottom: 6px; display:block; }
.monster-name { font-family:'Cinzel',serif;font-size:0.85rem;color:var(--gold-light);margin-bottom:4px; }
.monster-stats { font-size:0.75rem;color:var(--text-dim); }
.monster-level { position:absolute;top:6px;right:6px;background:var(--dark4);border:1px solid var(--border);border-radius:3px;padding:2px 6px;font-size:0.65rem;color:var(--gold);font-family:'Cinzel',serif; }

/* === BATTLE ARENA === */
.battle-arena {
    background: linear-gradient(135deg, #0d0505 0%, #1a0808 50%, #0d0505 100%);
    border: 2px solid #4a1010;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    position: relative;
    overflow: hidden;
    min-height: 200px;
}
.battle-arena::before {
    content:'⚔️';
    position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
    font-size:8rem;opacity:0.03;pointer-events:none;
}
.battle-result-won { animation: flash-win 0.5s ease; }
.battle-result-loss { animation: flash-loss 0.5s ease; }
@keyframes flash-win {
    0%{background:#00330a}50%{background:#004d10}100%{background:inherit}
}
@keyframes flash-loss {
    0%{background:#330000}50%{background:#4d0000}100%{background:inherit}
}
.battle-log-list {
    max-height: 200px;
    overflow-y: auto;
    text-align: left;
    background: var(--dark2);
    border: 1px solid var(--border);
    border-radius: 4px;
    padding: 10px;
    font-size: 0.85rem;
    margin: 12px 0;
}
.battle-log-list::-webkit-scrollbar{width:4px}
.battle-log-list::-webkit-scrollbar-track{background:var(--dark)}
.battle-log-list::-webkit-scrollbar-thumb{background:var(--border)}
.hit-player { color: #5dde8a; display:block;margin-bottom:3px; }
.hit-enemy { color: #de5d5d; display:block;margin-bottom:3px; }

/* === ITEMS === */
.item-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill,minmax(200px,1fr));
    gap: 8px;
    max-height: 500px;
    overflow-y: auto;
}
.item-card {
    background: var(--dark3);
    border: 1px solid var(--border);
    border-radius: 4px;
    padding: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.2s;
}
.item-card:hover { border-color:var(--gold);background:var(--dark4); }
.item-icon { font-size:1.5rem; flex-shrink:0; }
.item-info { flex:1; }
.item-name { font-size:0.85rem;font-weight:600; }
.item-effect { font-size:0.72rem;color:var(--text-dim); }
.item-actions { display:flex;gap:4px;margin-top:6px; }
.rarity-common { color:var(--rarity-common) }
.rarity-uncommon { color:var(--rarity-uncommon) }
.rarity-rare { color:var(--rarity-rare) }
.rarity-epic { color:var(--rarity-epic) }
.rarity-legendary { color:var(--rarity-legendary) }
.equipped-badge {
    display:inline-block;background:var(--gold);color:#000;
    font-size:0.6rem;padding:1px 5px;border-radius:2px;
    font-family:'Cinzel',serif;font-weight:700;letter-spacing:1px;
    margin-left:4px;
}

/* === SKILL LIST === */
.skill-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill,minmax(220px,1fr));
    gap: 10px;
}
.skill-card {
    background: var(--dark3);
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 14px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    transition:all 0.2s;
}
.skill-card:hover{border-color:var(--purple);background:var(--dark4);}
.skill-card .skill-icon{font-size:2rem;}
.skill-card .skill-name{font-family:'Cinzel',serif;color:var(--gold-light);font-size:0.9rem;}
.skill-card .skill-desc{font-size:0.8rem;color:var(--text-dim);}
.skill-card .skill-cost{font-size:0.75rem;color:#2980b9;}
.skill-card .skill-mult{font-size:0.75rem;color:#e67e22;}

/* === SHOP === */
.shop-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill,minmax(220px,1fr));
    gap: 10px;
    max-height: 600px;
    overflow-y:auto;
}
.shop-item {
    background: var(--dark3);
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 14px;
    text-align: center;
    transition: all 0.2s;
}
.shop-item:hover{border-color:var(--gold);transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,0.4);}
.shop-icon{font-size:2rem;display:block;margin-bottom:8px;}
.shop-name{font-family:'Cinzel',serif;font-size:0.85rem;color:var(--gold-light);margin-bottom:4px;}
.shop-effect{font-size:0.75rem;color:var(--text-dim);margin-bottom:8px;}
.shop-price{font-size:0.9rem;color:var(--gold);font-weight:700;margin-bottom:8px;}
.stock-badge{display:inline-block;font-size:0.65rem;padding:2px 8px;border-radius:10px;margin-bottom:6px;}
.stock-common{background:rgba(170,170,170,0.15);color:#aaa;border:1px solid #555;}
.stock-uncommon{background:rgba(74,245,66,0.1);color:#4af542;border:1px solid #4af542;}
.stock-rare{background:rgba(64,144,255,0.1);color:#4090ff;border:1px solid #4090ff;}
.stock-epic{background:rgba(204,68,255,0.1);color:#cc44ff;border:1px solid #cc44ff;}

/* === QUESTS === */
.quest-list { display:flex;flex-direction:column;gap:10px; }
.quest-card {
    background:var(--dark3);border:1px solid var(--border);border-radius:6px;padding:14px;
    display:flex;align-items:center;gap:14px;transition:all 0.2s;
}
.quest-card:hover{border-color:var(--gold);}
.quest-icon{font-size:2rem;flex-shrink:0;}
.quest-info{flex:1;}
.quest-name{font-family:'Cinzel',serif;color:var(--gold-light);font-size:0.9rem;margin-bottom:4px;}
.quest-desc{font-size:0.8rem;color:var(--text-dim);margin-bottom:6px;}
.quest-reward{font-size:0.78rem;color:var(--green);}
.quest-status{font-size:0.75rem;padding:3px 10px;border-radius:3px;font-family:'Cinzel',serif;}
.q-done{background:rgba(39,174,96,0.2);color:#27ae60;border:1px solid #27ae60;}
.q-active{background:rgba(212,175,55,0.15);color:var(--gold);border:1px solid var(--gold);}
.q-locked{background:rgba(100,100,100,0.2);color:#666;border:1px solid #444;}

/* === LEADERBOARD === */
.lb-table{width:100%;border-collapse:collapse;}
.lb-table th{font-family:'Cinzel',serif;color:var(--gold);font-size:0.75rem;padding:8px;border-bottom:1px solid var(--border);text-transform:uppercase;letter-spacing:1px;}
.lb-table td{padding:8px;font-size:0.85rem;border-bottom:1px solid rgba(74,62,32,0.3);}
.lb-table tr:hover td{background:var(--dark4);}
.lb-rank{font-family:'Cinzel',serif;font-weight:700;}
.rank-1{color:#FFD700;}
.rank-2{color:#C0C0C0;}
.rank-3{color:#CD7F32;}

/* === DUNGEON === */
.dungeon-map {
    display:grid;grid-template-columns:repeat(10,1fr);gap:4px;
    margin-bottom:16px;
}
.dungeon-cell{
    aspect-ratio:1;background:var(--dark4);border:1px solid var(--dark5);
    border-radius:2px;display:flex;align-items:center;justify-content:center;
    font-size:0.7rem;
}
.dungeon-cell.cleared{background:rgba(39,174,96,0.2);border-color:var(--green);}
.dungeon-cell.current{background:rgba(212,175,55,0.3);border-color:var(--gold);animation:pulse-cell 1s ease-in-out infinite;}
.dungeon-cell.locked{opacity:0.3;}
@keyframes pulse-cell{0%,100%{box-shadow:0 0 5px rgba(212,175,55,0.3)}50%{box-shadow:0 0 15px rgba(212,175,55,0.8)}}

/* === LOGIN/REGISTER === */
.auth-wrap {
    min-height: calc(100vh - 80px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
}
.auth-container {
    width: 100%;
    max-width: 900px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    border: 1px solid var(--gold);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 0 60px rgba(212,175,55,0.2);
}
@media(max-width:700px){.auth-container{grid-template-columns:1fr;}}
.auth-hero {
    background: linear-gradient(135deg, #0a0500 0%, #1a0d00 50%, #0a0500 100%);
    padding: 50px 40px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    position: relative;
    overflow: hidden;
    border-right: 1px solid var(--border);
}
.auth-hero::before{
    content:'🐉';position:absolute;font-size:12rem;opacity:0.05;
    top:50%;left:50%;transform:translate(-50%,-50%);
    animation:float-dragon 6s ease-in-out infinite;
}
@keyframes float-dragon{0%,100%{transform:translate(-50%,-50%) rotate(-5deg)}50%{transform:translate(-50%,-52%) rotate(5deg)}}
.auth-title {
    font-family:'Cinzel',serif;font-size:2.5rem;font-weight:900;
    color:var(--gold);
    text-shadow:0 0 30px rgba(212,175,55,0.8);
    letter-spacing:4px;
    margin-bottom:16px;line-height:1.2;
    position:relative;
}
.auth-subtitle{color:var(--text-dim);font-style:italic;font-size:1.1rem;position:relative;}
.auth-features{margin-top:30px;text-align:left;position:relative;}
.auth-features li{list-style:none;color:var(--text-dim);margin-bottom:8px;font-size:0.9rem;}
.auth-features li::before{content:'⚔️ ';margin-right:4px;}
.auth-form {
    background: var(--dark2);
    padding: 50px 40px;
}
.auth-tabs{display:flex;gap:0;margin-bottom:30px;border:1px solid var(--border);border-radius:4px;overflow:hidden;}
.auth-tab{flex:1;padding:10px;text-align:center;cursor:pointer;font-family:'Cinzel',serif;font-size:0.85rem;letter-spacing:1px;text-transform:uppercase;transition:all 0.2s;background:var(--dark3);color:var(--text-dim);}
.auth-tab.active{background:var(--dark5);color:var(--gold);border-bottom:2px solid var(--gold);}
.form-group{margin-bottom:16px;}
.form-group label{display:block;font-family:'Cinzel',serif;font-size:0.75rem;color:var(--text-dim);letter-spacing:1px;text-transform:uppercase;margin-bottom:6px;}
.form-group input, .form-group select{
    width:100%;background:var(--dark3);border:1px solid var(--border);
    color:var(--text);padding:10px 14px;border-radius:4px;
    font-family:'Crimson Text',serif;font-size:1rem;
    transition:border-color 0.2s;
}
.form-group input:focus, .form-group select:focus{
    outline:none;border-color:var(--gold);
    box-shadow:0 0 10px rgba(212,175,55,0.2);
}
.form-group select option{background:var(--dark2);}
.class-preview {
    background:var(--dark3);border:1px solid var(--border);border-radius:4px;
    padding:10px;margin-top:8px;font-size:0.8rem;color:var(--text-dim);
    min-height:50px;transition:all 0.2s;
}
.submit-btn {
    width:100%;padding:14px;
    background:linear-gradient(135deg,#8a6a00,#d4af37,#8a6a00);
    border:none;color:#000;font-family:'Cinzel',serif;
    font-size:1rem;font-weight:700;letter-spacing:2px;
    cursor:pointer;border-radius:4px;text-transform:uppercase;
    transition:all 0.3s;box-shadow:0 4px 15px rgba(212,175,55,0.3);
    margin-top:8px;
}
.submit-btn:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(212,175,55,0.5);}

/* === ALERTS === */
.alert {
    padding:10px 16px;border-radius:4px;margin-bottom:14px;
    font-size:0.9rem;border-left:3px solid;
    animation:slide-in 0.3s ease;
}
@keyframes slide-in{from{transform:translateX(-10px);opacity:0}to{transform:translateX(0);opacity:1}}
.alert-success{background:rgba(39,174,96,0.15);border-color:var(--green);color:#5dde8a;}
.alert-error{background:rgba(192,57,43,0.15);border-color:var(--red-glow);color:#ff7070;}
.alert-info{background:rgba(41,128,185,0.15);border-color:var(--blue);color:#5db8de;}

/* === MISC === */
.gold-text{color:var(--gold);}
.section-divider{border:none;border-top:1px solid var(--border);margin:14px 0;}
.text-center{text-align:center;}
.mb-8{margin-bottom:8px;}
.mb-12{margin-bottom:12px;}
.flex-between{display:flex;justify-content:space-between;align-items:center;}
.badge {
    display:inline-block;padding:2px 8px;border-radius:10px;font-size:0.7rem;
    font-family:'Cinzel',serif;letter-spacing:1px;text-transform:uppercase;
}
.badge-level{background:rgba(212,175,55,0.2);color:var(--gold);border:1px solid var(--gold);}
.badge-class{background:rgba(142,68,173,0.2);color:#cc88ff;border:1px solid #8e44ad;}

/* === SKILL SELECT IN BATTLE === */
.skill-select-row{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;}
.skill-btn-battle{
    background:var(--dark3);border:1px solid var(--purple);
    color:#cc88ff;padding:6px 12px;border-radius:4px;
    cursor:pointer;font-size:0.78rem;transition:all 0.2s;
    font-family:'Cinzel',serif;
}
.skill-btn-battle:hover,.skill-btn-battle.selected{
    background:rgba(142,68,173,0.3);box-shadow:0 0 10px rgba(142,68,173,0.4);
}

/* === DUNGEON FLOORS === */
.floor-display{
    text-align:center;padding:30px;
    background:linear-gradient(135deg,#0d0808,#1a0d0d);
    border:2px solid #4a1010;border-radius:8px;margin-bottom:16px;
}
.floor-number{font-family:'Cinzel',serif;font-size:3rem;color:var(--gold);font-weight:900;line-height:1;}
.floor-label{font-size:0.85rem;color:var(--text-dim);letter-spacing:3px;text-transform:uppercase;}

/* === CLASS ICONS === */
.class-badge{
    display:inline-flex;align-items:center;gap:6px;
    background:var(--dark4);border:1px solid var(--border);
    border-radius:3px;padding:4px 10px;font-size:0.8rem;
}

/* === SCROLLBAR GLOBAL === */
::-webkit-scrollbar{width:6px;height:6px}
::-webkit-scrollbar-track{background:var(--dark)}
::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px}
::-webkit-scrollbar-thumb:hover{background:var(--gold)}

/* === ANIMATIONS === */
@keyframes bounce-in{
    0%{transform:scale(0.8);opacity:0}
    60%{transform:scale(1.05)}
    100%{transform:scale(1);opacity:1}
}
.bounce-in{animation:bounce-in 0.4s ease;}

.victory-text{
    font-family:'Cinzel',serif;font-size:2rem;color:var(--green);
    font-weight:900;text-shadow:0 0 30px rgba(39,174,96,0.8);
    animation:pulse-text 1s ease-in-out infinite;
}
.defeat-text{
    font-family:'Cinzel',serif;font-size:2rem;color:var(--red-glow);
    font-weight:900;text-shadow:0 0 30px rgba(231,76,60,0.8);
}
@keyframes pulse-text{0%,100%{opacity:1}50%{opacity:0.7}}

.glow-box{
    animation:glow-border 2s ease-in-out infinite;
}
@keyframes glow-border{
    0%,100%{box-shadow:0 0 5px rgba(212,175,55,0.2)}
    50%{box-shadow:0 0 20px rgba(212,175,55,0.6)}
}

/* Responsive tabs on mobile */
@media(max-width:700px){
    .nav-tabs{display:none;}
    .logo{font-size:1.2rem;}
    .main-wrap{padding:10px;}
}
</style>
</head>
<body>

<!-- PARTICLES -->
<div class="particles" id="particles"></div>

<!-- HEADER -->
<header>
    <div class="logo">⚔️ Dragon<span>Realm</span></div>
    <?php if ($player): ?>
    <div class="nav-tabs" id="navTabs">
        <button class="tab-btn active" onclick="showTab('dashboard','this')">🏰 Town</button>
        <button class="tab-btn" onclick="showTab('battle','this')">⚔️ Battle</button>
        <button class="tab-btn" onclick="showTab('dungeon','this')">🏚️ Dungeon</button>
        <button class="tab-btn" onclick="showTab('inventory','this')">🎒 Bag</button>
        <button class="tab-btn" onclick="showTab('skills','this')">✨ Skills</button>
        <button class="tab-btn" onclick="showTab('shop','this')">🏪 Shop</button>
        <button class="tab-btn" onclick="showTab('quests','this')">📜 Quests</button>
        <button class="tab-btn" onclick="showTab('leaderboard','this')">🏆 Rank</button>
    </div>
    <div class="player-bar">
        <div class="player-avatar"><?= $CLASSES[$player['class']]['icon'] ?? '⚔️' ?></div>
        <div>
            <div class="player-name"><?= htmlspecialchars($player['username']) ?></div>
            <div class="player-stats-mini">Lv.<?= $player['level'] ?> <?= ucfirst($player['class']) ?> | 💛 <?= $player['gold'] ?></div>
        </div>
        <form method="post" style="margin:0">
            <input type="hidden" name="action" value="logout">
            <button class="btn btn-red btn-sm" type="submit">Exit</button>
        </form>
    </div>
    <?php endif; ?>
</header>

<div class="main-wrap">

<?php if (!$player): ?>
<!-- ===== AUTH PAGE ===== -->
<div class="auth-wrap">
    <div class="auth-container bounce-in">
        <div class="auth-hero">
            <div class="auth-title">DRAGON<br>REALM</div>
            <div class="auth-subtitle">An Epic Fantasy Adventure</div>
            <ul class="auth-features">
                <li>5 Unique Character Classes</li>
                <li>Real-Time Turn-Based Combat</li>
                <li>Dynamic Dungeon Exploration</li>
                <li>Hundreds of Items & Gear</li>
                <li>Skill Trees & Magic Spells</li>
                <li>Guild & Leaderboard System</li>
            </ul>
        </div>
        <div class="auth-form">
            <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>"><?= $message ?></div>
            <?php endif; ?>
            <div class="auth-tabs">
                <div class="auth-tab <?= empty($_SESSION['show_login']) ? 'active' : '' ?>" onclick="switchAuthTab('login')">Login</div>
                <div class="auth-tab <?= !empty($_SESSION['show_login']) ? 'active' : '' ?>" onclick="switchAuthTab('register')">Register</div>
            </div>
            <!-- LOGIN -->
            <div id="loginForm" <?= !empty($_SESSION['show_login']) ? 'style="display:none"' : '' ?>>
                <form method="post">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" placeholder="Enter your hero name..." required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Your secret password..." required>
                    </div>
                    <button type="submit" class="submit-btn">⚔️ Enter the Realm</button>
                </form>
            </div>
            <!-- REGISTER -->
            <div id="registerForm" <?= empty($_SESSION['show_login']) ? 'style="display:none"' : '' ?>>
                <form method="post">
                    <input type="hidden" name="action" value="register">
                    <div class="form-group">
                        <label>Choose Username</label>
                        <input type="text" name="username" placeholder="Your hero name..." required minlength="3" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Create password..." required minlength="4">
                    </div>
                    <div class="form-group">
                        <label>Choose Class</label>
                        <select name="class" id="classSelect" onchange="updateClassPreview()">
                            <?php foreach ($CLASSES as $k => $c): ?>
                            <option value="<?= $k ?>"><?= $c['icon'] ?> <?= $c['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="class-preview" id="classPreview">
                            <?php $fc = reset($CLASSES); ?>
                            <b><?= $fc['icon'] ?> <?= $fc['name'] ?></b> — <?= $fc['desc'] ?><br>
                            <small>HP: <?= $fc['hp'] ?> | MP: <?= $fc['mp'] ?> | ATK: <?= $fc['atk'] ?> | DEF: <?= $fc['def'] ?></small>
                        </div>
                    </div>
                    <button type="submit" class="submit-btn">🐉 Create Hero</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ===== GAME PAGE ===== -->
<?php if ($message): ?>
<div class="alert alert-<?= $message_type ?>" id="gameAlert"><?= $message ?></div>
<?php endif; ?>

<div class="game-grid">

<!-- LEFT: PLAYER STATS -->
<div>
    <div class="panel glow-box" style="margin-bottom:14px;">
        <div class="panel-title">⚔️ Hero Stats</div>
        <div class="text-center mb-8">
            <div style="font-size:3rem;margin-bottom:6px;"><?= $CLASSES[$player['class']]['icon'] ?></div>
            <div style="font-family:'Cinzel',serif;font-size:1.2rem;color:var(--gold-light);"><?= htmlspecialchars($player['username']) ?></div>
            <div style="margin-top:6px;">
                <span class="badge badge-level">Lv. <?= $player['level'] ?></span>
                &nbsp;
                <span class="badge badge-class"><?= $CLASSES[$player['class']]['name'] ?></span>
            </div>
        </div>
        <div class="stat-bar-wrap">
            <div class="stat-label"><span>❤️ HP</span><span><?= $player['hp'] ?>/<?= $player['max_hp'] ?></span></div>
            <div class="stat-bar"><div class="stat-bar-fill hp-bar" style="width:<?= min(100,round($player['hp']/$player['max_hp']*100)) ?>%"></div></div>
        </div>
        <div class="stat-bar-wrap">
            <div class="stat-label"><span>💧 MP</span><span><?= $player['mp'] ?>/<?= $player['max_mp'] ?></span></div>
            <div class="stat-bar"><div class="stat-bar-fill mp-bar" style="width:<?= min(100,round($player['mp']/$player['max_mp']*100)) ?>%"></div></div>
        </div>
        <div class="stat-bar-wrap">
            <div class="stat-label"><span>⭐ EXP</span><span><?= $player['exp'] ?>/<?= $player['exp_next'] ?></span></div>
            <div class="stat-bar"><div class="stat-bar-fill exp-bar" style="width:<?= min(100,round($player['exp']/$player['exp_next']*100)) ?>%"></div></div>
        </div>
        <div class="stats-grid" style="margin-top:12px;">
            <div class="stat-item"><div class="val">⚔️ <?= $player['attack'] ?></div><div class="lbl">Attack</div></div>
            <div class="stat-item"><div class="val">🛡️ <?= $player['defense'] ?></div><div class="lbl">Defense</div></div>
            <div class="stat-item"><div class="val">💨 <?= $player['speed'] ?></div><div class="lbl">Speed</div></div>
            <div class="stat-item"><div class="val">🔮 <?= $player['magic'] ?></div><div class="lbl">Magic</div></div>
            <div class="stat-item"><div class="val">💛 <?= $player['gold'] ?></div><div class="lbl">Gold</div></div>
            <div class="stat-item"><div class="val">💎 <?= $player['gems'] ?></div><div class="lbl">Gems</div></div>
            <div class="stat-item"><div class="val">☠️ <?= $player['kills'] ?></div><div class="lbl">Kills</div></div>
            <div class="stat-item"><div class="val">💀 <?= $player['deaths'] ?></div><div class="lbl">Deaths</div></div>
        </div>
    </div>

    <!-- INN -->
    <div class="panel" style="margin-bottom:14px;">
        <div class="panel-title">🏠 The Inn</div>
        <p style="font-size:0.85rem;color:var(--text-dim);margin-bottom:10px;">Rest to restore all HP & MP</p>
        <form method="post">
            <input type="hidden" name="action" value="inn_heal">
            <button class="btn btn-green btn-full" type="submit">🛌 Rest (50 Gold)</button>
        </form>
    </div>

    <!-- BATTLE LOG -->
    <div class="panel">
        <div class="panel-title">📜 Battle History</div>
        <?php if ($battle_log): foreach ($battle_log as $log): ?>
        <div style="border-bottom:1px solid var(--border);padding:6px 0;font-size:0.8rem;">
            <span style="color:<?= $log['result']==='win'?'var(--green)':'var(--red-glow)' ?>">
                <?= $log['result']==='win'?'✅':'❌' ?>
            </span>
            <?= htmlspecialchars($log['enemy_name']) ?>
            <?php if ($log['exp_gained']>0): ?><span style="color:var(--gold);"> +<?= $log['exp_gained'] ?> EXP</span><?php endif; ?>
        </div>
        <?php endforeach; else: ?>
        <p style="color:var(--text-dim);font-size:0.85rem;">No battles yet. Go fight!</p>
        <?php endif; ?>
    </div>
</div>

<!-- CENTER: MAIN CONTENT -->
<div>
    <!-- DASHBOARD TAB -->
    <div class="tab-content active" id="tab-dashboard">
        <div class="panel" style="margin-bottom:14px;">
            <div class="panel-title">🏰 Town of Aldenmoor</div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px;">
                <div style="background:var(--dark3);border:1px solid var(--border);border-radius:6px;padding:16px;text-align:center;cursor:pointer;" onclick="showTab('battle')">
                    <div style="font-size:2rem;margin-bottom:6px;">⚔️</div>
                    <div style="font-family:'Cinzel',serif;font-size:0.8rem;color:var(--gold);">Battle Field</div>
                    <div style="font-size:0.75rem;color:var(--text-dim);margin-top:4px;">Fight monsters for EXP</div>
                </div>
                <div style="background:var(--dark3);border:1px solid var(--border);border-radius:6px;padding:16px;text-align:center;cursor:pointer;" onclick="showTab('dungeon')">
                    <div style="font-size:2rem;margin-bottom:6px;">🏚️</div>
                    <div style="font-family:'Cinzel',serif;font-size:0.8rem;color:var(--gold);">Dark Dungeon</div>
                    <div style="font-size:0.75rem;color:var(--text-dim);margin-top:4px;">Floor <?= $player['dungeon_floor'] ?></div>
                </div>
                <div style="background:var(--dark3);border:1px solid var(--border);border-radius:6px;padding:16px;text-align:center;cursor:pointer;" onclick="showTab('shop')">
                    <div style="font-size:2rem;margin-bottom:6px;">🏪</div>
                    <div style="font-family:'Cinzel',serif;font-size:0.8rem;color:var(--gold);">Market</div>
                    <div style="font-size:0.75rem;color:var(--text-dim);margin-top:4px;">Buy gear & potions</div>
                </div>
            </div>
            <div style="background:var(--dark3);border:1px solid var(--border);border-radius:6px;padding:16px;">
                <div style="font-family:'Cinzel',serif;color:var(--gold);margin-bottom:10px;">📊 Hero Summary</div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;text-align:center;">
                    <div><div style="font-size:1.5rem;color:var(--gold-light);font-weight:700;"><?= $player['wins'] ?></div><div style="font-size:0.75rem;color:var(--text-dim);">WINS</div></div>
                    <div><div style="font-size:1.5rem;color:var(--red-glow);font-weight:700;"><?= $player['losses'] ?></div><div style="font-size:0.75rem;color:var(--text-dim);">LOSSES</div></div>
                    <div><div style="font-size:1.5rem;color:var(--green);font-weight:700;"><?= $player['dungeon_floor'] ?></div><div style="font-size:0.75rem;color:var(--text-dim);">DUNGEON FL.</div></div>
                </div>
            </div>
        </div>
        <!-- TOP LEADERBOARD PREVIEW -->
        <div class="panel">
            <div class="panel-title">🏆 Top Heroes</div>
            <table class="lb-table">
                <tr><th>#</th><th>Hero</th><th>Class</th><th>Lv</th><th>Kills</th></tr>
                <?php foreach ($leaderboard as $i => $row): ?>
                <tr>
                    <td class="lb-rank <?= $i<3?'rank-'.($i+1):'' ?>"><?= $i<3?['👑','🥈','🥉'][$i]:($i+1) ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= $CLASSES[$row['class']]['icon'] ?> <?= $row['class'] ?></td>
                    <td style="color:var(--gold)"><?= $row['level'] ?></td>
                    <td><?= $row['kills'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <!-- BATTLE TAB -->
    <div class="tab-content" id="tab-battle">
        <div class="panel" style="margin-bottom:14px;">
            <div class="panel-title">⚔️ Choose Your Enemy</div>

            <?php if ($battle_result): ?>
            <div class="battle-arena bounce-in <?= $battle_result['won']?'battle-result-won':'battle-result-loss' ?>">
                <div class="<?= $battle_result['won']?'victory-text':'defeat-text' ?>">
                    <?= $battle_result['won']?'⚔️ VICTORY! ⚔️':'💀 DEFEATED 💀' ?>
                </div>
                <div style="font-size:3rem;margin:10px 0;"><?= $battle_result['monster']['icon'] ?></div>
                <div style="font-family:'Cinzel',serif;font-size:1rem;color:var(--text-dim);">vs <?= $battle_result['monster']['name'] ?></div>
                <?php if ($battle_result['won']): ?>
                <div style="margin-top:10px;color:var(--gold);">+<?= $battle_result['exp_gain'] ?> EXP | +<?= $battle_result['gold_gain'] ?> Gold</div>
                <?php endif; ?>
                <div class="battle-log-list">
                    <?php foreach ($battle_result['rounds'] as $r) echo $r.'<br>'; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- SKILL SELECTION -->
            <div style="margin-bottom:14px;">
                <div style="font-family:'Cinzel',serif;font-size:0.8rem;color:var(--text-dim);margin-bottom:8px;letter-spacing:1px;text-transform:uppercase;">🔮 Select Skill (optional)</div>
                <div class="skill-select-row" id="skillSelectRow">
                    <button class="skill-btn-battle selected" onclick="selectSkill('',this)">⚔️ Normal Attack</button>
                    <?php global $SKILLS_DATA; foreach (($SKILLS_DATA[$player['class']] ?? []) as $sk): ?>
                    <button class="skill-btn-battle" onclick="selectSkill('<?= htmlspecialchars($sk['name']) ?>',this)" title="<?= $sk['desc'] ?> | MP: <?= $sk['mp'] ?> | x<?= $sk['dmg_mult'] ?>">
                        <?= $sk['icon'] ?> <?= $sk['name'] ?> (<?= $sk['mp'] ?>MP)
                    </button>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="selectedSkill" value="">
            </div>

            <div class="monster-grid">
                <?php foreach ($MONSTERS as $i => $m): ?>
                <div class="monster-card">
                    <span class="monster-level">Lv.<?= $m['level'] ?></span>
                    <span class="monster-icon"><?= $m['icon'] ?></span>
                    <div class="monster-name"><?= $m['name'] ?></div>
                    <div class="monster-stats">
                        ❤️ <?= $m['hp'] ?> | ⚔️ <?= $m['atk'] ?> | 🛡️ <?= $m['def'] ?><br>
                        ⭐ <?= $m['exp'] ?> EXP | 💛 <?= $m['gold'] ?> Gold
                    </div>
                    <div style="font-size:0.72rem;color:var(--text-dim);margin-top:4px;font-style:italic;"><?= $m['desc'] ?></div>
                    <form method="post" style="margin-top:10px;">
                        <input type="hidden" name="action" value="battle">
                        <input type="hidden" name="monster_idx" value="<?= $i ?>">
                        <input type="hidden" name="skill_used" id="battle-skill-<?= $i ?>">
                        <button class="btn btn-red btn-full" type="submit" onclick="document.getElementById('battle-skill-<?= $i ?>').value=document.getElementById('selectedSkill').value">⚔️ Attack!</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- DUNGEON TAB -->
    <div class="tab-content" id="tab-dungeon">
        <div class="panel">
            <div class="panel-title">🏚️ The Dark Dungeon</div>
            <div class="floor-display">
                <div class="floor-number"><?= $player['dungeon_floor'] ?></div>
                <div class="floor-label">Current Floor</div>
                <div style="font-size:0.85rem;color:var(--text-dim);margin-top:8px;">Each floor gets harder. Defeat the guardian to advance!</div>
            </div>

            <!-- Mini dungeon map -->
            <div class="dungeon-map">
                <?php for ($f = 1; $f <= 30; $f++): ?>
                <div class="dungeon-cell <?= $f < $player['dungeon_floor'] ? 'cleared' : ($f === $player['dungeon_floor'] ? 'current' : 'locked') ?>">
                    <?php if ($f < $player['dungeon_floor']): ?>✅
                    <?php elseif ($f === $player['dungeon_floor']): ?>⚔️
                    <?php else: ?>🔒<?php endif; ?>
                </div>
                <?php endfor; ?>
            </div>

            <?php if ($battle_result && isset($battle_result['dungeon'])): ?>
            <div class="battle-arena bounce-in <?= $battle_result['won']?'battle-result-won':'battle-result-loss' ?>">
                <div class="<?= $battle_result['won']?'victory-text':'defeat-text' ?>">
                    <?= $battle_result['won']?'🏰 FLOOR CLEARED!':'💀 FLOOR FAILED' ?>
                </div>
                <div style="margin:10px 0;font-size:2rem;"><?= $battle_result['monster']['icon'] ?></div>
                <?php if ($battle_result['won']): ?>
                <div style="color:var(--gold);">+<?= $battle_result['exp_gain'] ?> EXP | +<?= $battle_result['gold_gain'] ?> Gold</div>
                <?php endif; ?>
                <div class="battle-log-list"><?php foreach ($battle_result['rounds'] as $r) echo $r.'<br>'; ?></div>
            </div>
            <?php endif; ?>

            <div style="margin-top:14px;text-align:center;">
                <div style="background:var(--dark3);border:1px solid var(--border);border-radius:6px;padding:16px;margin-bottom:14px;">
                    <div style="font-family:'Cinzel',serif;color:var(--gold);margin-bottom:8px;">Floor <?= $player['dungeon_floor'] ?> Guardian</div>
                    <?php
                    $fi = min(count($MONSTERS)-1, intval($player['dungeon_floor']/3));
                    $bm = $MONSTERS[$fi];
                    $bm_hp = intval($bm['hp'] * (1 + $player['dungeon_floor'] * 0.2));
                    ?>
                    <div style="font-size:3rem;"><?= $bm['icon'] ?></div>
                    <div style="font-family:'Cinzel',serif;color:var(--text);"><?= $bm['name'] ?></div>
                    <div style="font-size:0.8rem;color:var(--text-dim);margin-top:6px;">❤️ <?= $bm_hp ?> | ⚔️ <?= intval($bm['atk']*(1+$player['dungeon_floor']*0.1)) ?></div>
                </div>
                <form method="post">
                    <input type="hidden" name="action" value="enter_dungeon">
                    <button class="btn btn-gold" style="padding:14px 40px;font-size:1rem;" type="submit">🏚️ Enter Floor <?= $player['dungeon_floor'] ?></button>
                </form>
            </div>
        </div>
    </div>

    <!-- INVENTORY TAB -->
    <div class="tab-content" id="tab-inventory">
        <div class="panel">
            <div class="panel-title">🎒 Inventory</div>
            <?php if ($inventory): ?>
            <div class="item-grid">
                <?php foreach ($inventory as $item): ?>
                <div class="item-card">
                    <span class="item-icon">
                        <?php
                        global $ITEMS_SHOP;
                        $found_icon = '📦';
                        foreach ($ITEMS_SHOP as $si) if ($si['name']===$item['item_name']){$found_icon=$si['icon'];break;}
                        echo $found_icon;
                        ?>
                    </span>
                    <div class="item-info">
                        <div class="item-name rarity-<?= $item['item_rarity'] ?>">
                            <?= htmlspecialchars($item['item_name']) ?>
                            <?php if ($item['equipped']): ?><span class="equipped-badge">EQ</span><?php endif; ?>
                            <?php if ($item['quantity']>1): ?><span style="color:var(--text-dim);font-size:0.75rem;"> x<?= $item['quantity'] ?></span><?php endif; ?>
                        </div>
                        <div class="item-effect"><?= htmlspecialchars($item['item_effect']) ?></div>
                        <div class="item-actions">
                            <?php if ($item['item_type']==='consumable'): ?>
                            <form method="post"><input type="hidden" name="action" value="use_item"><input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                            <button class="btn btn-green btn-sm" type="submit">Use</button></form>
                            <?php elseif (in_array($item['item_type'],['weapon','armor','accessory']) && !$item['equipped']): ?>
                            <form method="post"><input type="hidden" name="action" value="equip_item"><input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                            <button class="btn btn-gold btn-sm" type="submit">Equip</button></form>
                            <?php endif; ?>
                            <form method="post"><input type="hidden" name="action" value="sell_item"><input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                            <button class="btn btn-red btn-sm" type="submit" onclick="return confirm('Sell for <?= intval($item['item_value']*0.5) ?> gold?')">Sell</button></form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p style="color:var(--text-dim);font-style:italic;">Your bag is empty. Visit the shop!</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- SKILLS TAB -->
    <div class="tab-content" id="tab-skills">
        <div class="panel">
            <div class="panel-title">✨ Skill Book</div>
            <div style="margin-bottom:14px;">
                <span class="class-badge"><?= $CLASSES[$player['class']]['icon'] ?> <?= $CLASSES[$player['class']]['name'] ?> Skills</span>
            </div>
            <div class="skill-grid">
                <?php global $SKILLS_DATA; foreach (($SKILLS_DATA[$player['class']] ?? []) as $sk): ?>
                <div class="skill-card">
                    <div class="skill-icon"><?= $sk['icon'] ?></div>
                    <div class="skill-name"><?= $sk['name'] ?></div>
                    <div class="skill-desc"><?= $sk['desc'] ?></div>
                    <div class="skill-cost">💧 MP Cost: <?= $sk['mp'] ?></div>
                    <div class="skill-mult">⚔️ Damage: <?= $sk['dmg_mult'] > 0 ? 'x'.$sk['dmg_mult'] : 'Special' ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- SHOP TAB -->
    <div class="tab-content" id="tab-shop">
        <div class="panel">
            <div class="panel-title">🏪 Merchant's Bazaar</div>
            <div style="margin-bottom:12px;display:flex;align-items:center;gap:10px;">
                <span style="font-size:1.1rem;">💛 Your Gold: <b style="color:var(--gold)"><?= $player['gold'] ?></b></span>
            </div>
            <div class="shop-grid">
                <?php foreach ($ITEMS_SHOP as $idx => $item): ?>
                <div class="shop-item">
                    <span class="shop-icon"><?= $item['icon'] ?></span>
                    <div class="shop-name"><?= $item['name'] ?></div>
                    <div class="stock-badge stock-<?= $item['rarity'] ?>"><?= ucfirst($item['rarity']) ?></div>
                    <div class="shop-effect"><?= $item['effect'] ?></div>
                    <div class="shop-price">💛 <?= $item['value'] ?> Gold</div>
                    <form method="post">
                        <input type="hidden" name="action" value="buy_item">
                        <input type="hidden" name="item_idx" value="<?= $idx ?>">
                        <button class="btn btn-gold btn-full" type="submit" <?= $player['gold'] < $item['value'] ? 'disabled style="opacity:0.4;cursor:not-allowed"' : '' ?>>🛒 Buy</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- QUESTS TAB -->
    <div class="tab-content" id="tab-quests">
        <div class="panel">
            <div class="panel-title">📜 Quest Board</div>
            <div class="quest-list">
                <?php global $QUESTS; foreach ($QUESTS as $q):
                    $done = false;
                    if ($q['req_kills'] > 0 && $player['kills'] >= $q['req_kills']) $done = true;
                    if ($q['id'] === 4 && $player['gold'] >= 500) $done = true;
                    if ($q['id'] === 5 && $player['dungeon_floor'] >= 5) $done = true;
                    if ($q['id'] === 6 && $player['kills'] > 0 && str_contains(implode(',', array_column($battle_log,'enemy_name')), 'Ancient Dragon')) $done = true;
                    $progress = 0;
                    if ($q['req_kills'] > 0) $progress = min(100, round($player['kills'] / $q['req_kills'] * 100));
                ?>
                <div class="quest-card">
                    <div class="quest-icon"><?= $q['icon'] ?></div>
                    <div class="quest-info">
                        <div class="quest-name"><?= $q['name'] ?></div>
                        <div class="quest-desc"><?= $q['desc'] ?></div>
                        <?php if ($q['req_kills']>0): ?>
                        <div class="stat-bar" style="margin-bottom:6px;margin-top:4px;"><div class="stat-bar-fill exp-bar" style="width:<?= $progress ?>%"></div></div>
                        <div style="font-size:0.72rem;color:var(--text-dim);">Progress: <?= min($player['kills'], $q['req_kills']) ?>/<?= $q['req_kills'] ?></div>
                        <?php endif; ?>
                        <div class="quest-reward">🎁 +<?= $q['reward_exp'] ?> EXP<?= $q['reward_gold']>0?' | 💛 +'.$q['reward_gold'].' Gold':'' ?></div>
                    </div>
                    <div>
                        <?php if ($done): ?>
                        <span class="quest-status q-done">✅ Done</span>
                        <?php else: ?>
                        <span class="quest-status q-active">⚔️ Active</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- LEADERBOARD TAB -->
    <div class="tab-content" id="tab-leaderboard">
        <div class="panel">
            <div class="panel-title">🏆 Hall of Legends</div>
            <table class="lb-table">
                <tr><th>#</th><th>Hero</th><th>Class</th><th>Level</th><th>Kills</th><th>Gold</th></tr>
                <?php foreach ($leaderboard as $i => $row): ?>
                <tr>
                    <td class="lb-rank <?= $i<3?'rank-'.($i+1):'' ?>"><?= $i<3?['👑','🥈','🥉'][$i]:($i+1) ?></td>
                    <td style="font-family:'Cinzel',serif;"><?= htmlspecialchars($row['username']) ?><?= $row['username']===$player['username']?' <span style="color:var(--gold);font-size:0.7rem;">(You)</span>':'' ?></td>
                    <td><?= $CLASSES[$row['class']]['icon'] ?> <?= ucfirst($row['class']) ?></td>
                    <td style="color:var(--gold);font-weight:700;"><?= $row['level'] ?></td>
                    <td>⚔️ <?= $row['kills'] ?></td>
                    <td>💛 <?= number_format($row['gold']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

</div><!-- end center -->

<!-- RIGHT: MINI INFO -->
<div>
    <!-- Win/Loss -->
    <div class="panel" style="margin-bottom:14px;">
        <div class="panel-title">📊 Combat Record</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;text-align:center;">
            <div style="background:rgba(39,174,96,0.1);border:1px solid var(--green);border-radius:4px;padding:12px;">
                <div style="font-size:1.5rem;color:var(--green);font-weight:700;"><?= $player['wins'] ?></div>
                <div style="font-size:0.7rem;color:var(--text-dim);">WINS</div>
            </div>
            <div style="background:rgba(192,57,43,0.1);border:1px solid var(--red-glow);border-radius:4px;padding:12px;">
                <div style="font-size:1.5rem;color:var(--red-glow);font-weight:700;"><?= $player['losses'] ?></div>
                <div style="font-size:0.7rem;color:var(--text-dim);">LOSSES</div>
            </div>
        </div>
        <?php
        $total = $player['wins'] + $player['losses'];
        $wr = $total > 0 ? round($player['wins']/$total*100) : 0;
        ?>
        <div style="margin-top:10px;">
            <div class="stat-label"><span style="font-size:0.78rem">Win Rate</span><span><?= $wr ?>%</span></div>
            <div class="stat-bar"><div class="stat-bar-fill" style="width:<?= $wr ?>%;background:linear-gradient(90deg,var(--green),var(--gold-light))"></div></div>
        </div>
    </div>

    <!-- Class Info -->
    <div class="panel" style="margin-bottom:14px;">
        <div class="panel-title">🧙 Class: <?= $CLASSES[$player['class']]['name'] ?></div>
        <div style="text-align:center;font-size:3rem;margin-bottom:10px;"><?= $CLASSES[$player['class']]['icon'] ?></div>
        <p style="font-size:0.85rem;color:var(--text-dim);text-align:center;font-style:italic;"><?= $CLASSES[$player['class']]['desc'] ?></p>
    </div>

    <!-- Quick Items -->
    <div class="panel" style="margin-bottom:14px;">
        <div class="panel-title">⚗️ Quick Use</div>
        <?php
        $potions = array_filter($inventory, fn($i) => $i['item_type'] === 'consumable');
        if ($potions): foreach (array_slice($potions, 0, 5) as $it): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
            <span style="font-size:0.85rem;"><?php
                $fi = '📦';
                foreach ($ITEMS_SHOP as $si) if ($si['name']===$it['item_name']){$fi=$si['icon'];break;}
                echo $fi.' '.htmlspecialchars($it['item_name']);
                if($it['quantity']>1) echo ' x'.$it['quantity'];
            ?></span>
            <form method="post"><input type="hidden" name="action" value="use_item"><input type="hidden" name="item_id" value="<?= $it['id'] ?>">
            <button class="btn btn-green btn-sm" type="submit">Use</button></form>
        </div>
        <?php endforeach; else: ?>
        <p style="color:var(--text-dim);font-size:0.8rem;">No consumables in bag.</p>
        <?php endif; ?>
    </div>

    <!-- Dungeon quick -->
    <div class="panel">
        <div class="panel-title">🏚️ Dungeon Status</div>
        <div style="text-align:center;padding:10px;">
            <div style="font-family:'Cinzel',serif;font-size:2.5rem;color:var(--gold);"><?= $player['dungeon_floor'] ?></div>
            <div style="font-size:0.75rem;color:var(--text-dim);letter-spacing:2px;text-transform:uppercase;">Floor Reached</div>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="enter_dungeon">
            <button class="btn btn-red btn-full" type="submit">⚔️ Challenge Floor <?= $player['dungeon_floor'] ?></button>
        </form>
    </div>
</div>

</div><!-- game-grid -->
<?php endif; ?>
</div><!-- main-wrap -->

<script>
// === CLASS DATA FOR PREVIEW ===
const classes = <?= json_encode($CLASSES) ?>;

function updateClassPreview() {
    const sel = document.getElementById('classSelect');
    const key = sel.value;
    const c = classes[key];
    if (!c) return;
    document.getElementById('classPreview').innerHTML =
        `<b>${c.icon} ${c.name}</b> — ${c.desc}<br>
        <small>HP: ${c.hp} | MP: ${c.mp} | ATK: ${c.atk} | DEF: ${c.def} | SPD: ${c.spd} | MAG: ${c.mag}</small>`;
}

// === AUTH TAB SWITCH ===
function switchAuthTab(tab) {
    document.getElementById('loginForm').style.display = tab==='login'?'block':'none';
    document.getElementById('registerForm').style.display = tab==='register'?'block':'none';
    document.querySelectorAll('.auth-tab').forEach((t,i)=>{
        t.classList.toggle('active', (i===0&&tab==='login')||(i===1&&tab==='register'));
    });
}

// === GAME TABS ===
function showTab(id, el) {
    document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));
    const tab = document.getElementById('tab-'+id);
    if (tab) tab.classList.add('active');
    document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
    if (el && typeof el === 'object') el.classList.add('active');
}

// === SKILL SELECT ===
function selectSkill(name, btn) {
    document.querySelectorAll('.skill-btn-battle').forEach(b=>b.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('selectedSkill').value = name;
}

// === PARTICLES ===
(function(){
    const container = document.getElementById('particles');
    if (!container) return;
    const colors = ['#d4af37','#e74c3c','#9b59b6','#3498db','#e67e22'];
    for (let i=0;i<25;i++){
        const p = document.createElement('div');
        p.className = 'particle';
        const size = Math.random()*4+1;
        p.style.cssText = `
            width:${size}px;height:${size}px;
            left:${Math.random()*100}%;
            background:${colors[Math.floor(Math.random()*colors.length)]};
            animation-duration:${Math.random()*15+8}s;
            animation-delay:${Math.random()*10}s;
            opacity:0.6;
        `;
        container.appendChild(p);
    }
})();

// === AUTO-DISMISS ALERT ===
setTimeout(()=>{
    const a = document.getElementById('gameAlert');
    if(a) { a.style.transition='opacity 0.5s'; a.style.opacity='0'; setTimeout(()=>a.remove(),500); }
},4000);

// === ACTIVE TAB FROM BATTLE ===
<?php if ($battle_result && isset($battle_result['dungeon'])): ?>
showTab('dungeon');
document.querySelectorAll('.tab-btn')[2]?.classList.add('active');
document.querySelectorAll('.tab-btn')[1]?.classList.remove('active');
document.querySelectorAll('.tab-btn')[0]?.classList.remove('active');
<?php elseif ($battle_result): ?>
document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
document.querySelector('.tab-btn:nth-child(2)')?.classList.add('active');
<?php endif; ?>
</script>
</body>
</html>

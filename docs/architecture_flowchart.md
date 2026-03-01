
# Word Weavers System Architecture

Since the image generation service is currently experiencing high traffic, I have created a comprehensive **Mermaid.js** architecture diagram. This has the added benefit of being version-controllable and editable.

## Architecture Flowchart

```mermaid
---
id: fb12ea6f-fd59-4ae5-abe9-1c08dc2576d0
---
flowchart TD
    %% Theme Definitions
    classDef purple fill:#7C3AED,stroke:#5B21B6,stroke-width:2px,color:white,rx:5,ry:5;
    classDef blue fill:#3B82F6,stroke:#1D4ED8,stroke-width:2px,color:white,rx:5,ry:5;
    classDef blueHigh fill:#2563EB,stroke:#1E40AF,stroke-width:4px,color:white,rx:5,ry:5;
    classDef green fill:#10B981,stroke:#047857,stroke-width:2px,color:white,rx:5,ry:5;
    classDef orange fill:#F59E0B,stroke:#B45309,stroke-width:2px,color:white,shape:cylinder;
    classDef gray fill:#F3F4F6,stroke:#d1d5db,stroke-width:1px,color:#1f2937,rx:2,ry:2;

    %% --- TOP SECTION: AUTHENTICATION (Purple) ---
    subgraph AuthLayer [Authentication Layer]
        direction TB
        UserAccess([User Access index.php <br/> fa:fa-user]):::purple
        
        subgraph NewReg [New User Reference]
            Register[Register Form <br/> fa:fa-user-plus]:::purple
            OTP[OTP Email Verification <br/> fa:fa-envelope]:::purple
            Hash[Password Hashing bcrypt]:::purple
            Create[Account Creation]:::purple
        end

        subgraph LoginFlow [Existing User Login]
            Login[Login Form <br/> fa:fa-sign-in-alt]:::purple
            CredCheck[Credential Check password_verify]:::purple
            SessionRegen[Session Regeneration]:::purple
            AuthSession[Authenticated Session]:::purple
        end

        SessionEst{Session Established}:::purple
    end

    %% --- MIDDLE SECTION ---
    
    %% Left Column: Student Portal (Blue)
    subgraph StudentPortal [Student Portal Flow]
        direction TB
        Menu[Main Dashboard <br/> fa:fa-th-large]:::blue
        
        Profile[Profile Management <br/> fa:fa-id-card]:::blue
        Settings[Update Settings <br/> fa:fa-cog]:::blue
        
        Friends[Friends System <br/> fa:fa-user-friends]:::blue
        Requests[Send/Accept Requests]:::blue
        
        Leader[Leaderboards <br/> fa:fa-trophy]:::blue
        Rankings[View Rankings]:::blue
        
        VocabGameNode[VocabWorld Game]:::blueHigh
    end

    %% Center Column: Gameplay Core (Highlighed Blue)
    subgraph GameCore [VocabWorld Gameplay Core]
        direction TB
        GameLaunch[Game Launch <br/> fa:fa-rocket]:::blueHigh
        Phaser[Phaser Engine <br/> fa:fa-gamepad]:::blueHigh
        LoadProg[Load Saved Progress]:::blueHigh
        Movement[Player Movement Loop]:::blueHigh
        Collision{Collision?}:::blueHigh
        Battle[Battle Encounter]:::blueHigh
        API[AJAX GET api/vocabulary.php]:::blueHigh
        Modal[Display Question Modal]:::blueHigh
        Answer{Answer Correct?}:::blueHigh
        
        Reward[+25 EXP, +5-10 Essence/Shards]:::blueHigh
        MgrUpdates[essence_manager.php & level_manager.php <br/> shard_manager.php]:::blueHigh
        
        Penalty[-10-25 HP, +5 EXP]:::blueHigh
        ShowAns[Show Correct Answer]:::blueHigh
        
        AutoSave[Save Progress every 60s]:::blueHigh
        SaveScript[Save Scripts <br/> fa:fa-save]:::blueHigh
        
        Shop[Shop & Customization <br/> fa:fa-shopping-cart]:::blueHigh
    end

    %% Right Column: Teacher Dashboard (Green)
    subgraph TeacherPortal [Teacher Dashboard Flow]
        direction TB
        CheckRole{Role = Teacher? <br/> fa:fa-chalkboard-teacher}:::green
        TDash[Teacher Dashboard <br/> fa:fa-chart-line]:::green
        
        VocabMod[Vocabulary Management]:::green
        VocabScript[vocabulary.php]:::green
        VocabCRUD[CRUD Operations]:::green
        
        LessonMod[Lesson Management]:::green
        LessonScript[lessons.php]:::green
        LessonAct[Create/Edit/Delete]:::green
        
        StudentMod[Student Monitoring]:::green
        StudentScript[students.php]:::green
        StudentQuery[JOIN Queries / Metrics]:::green
    end

    %% --- BOTTOM SECTION: DATABASE (Orange) ---
    subgraph DatabaseLayer [Database Layer]
        DB[(MySQL Database <br/> fa:fa-database)]:::orange
        Tables[Tables: users, game_progress, vocabulary_questions, vocabulary_choices, lessons, lesson_vocabulary, friendships, friend_requests, notifications, leaderboard_rankings, gwa_records, answer_history, user_shards, shard_transactions, character_selections]:::orange
    end

    %% --- RIGHT PANEL: EXTERNAL SERVICES ---
    subgraph External [External Services]
        Mailer[PHPMailer SMTP <br/> fa:fa-paper-plane]:::gray
        Host[Hosting <br/> fa:fa-server]:::gray
        SSL[SSL Encryption <br/> fa:fa-lock]:::gray
        CDN[CDN <br/> fa:fa-cloud]:::gray
    end

    %% CONNECTIONS
    %% Auth
    UserAccess --> NewReg
    UserAccess --> LoginFlow
    
    Register --> OTP --> Hash --> Create
    Create -- INSERT --> DB
    
    Login --> CredCheck --> SessionRegen --> AuthSession
    AuthSession --> SessionEst
    Create --> SessionEst

    %% Routing
    SessionEst --> Menu
    SessionEst -- Role Check --> CheckRole
    CheckRole -- Yes --> TDash

    %% Student Flows
    Menu --> Profile --> Settings -- UPDATE --> DB
    Menu --> Friends --> Requests -- INSERT/UPDATE --> DB
    Menu --> Leader --> Rankings -- SELECT --> DB
    Menu --> VocabGameNode --> GameLaunch

    %% Game Logic
    GameLaunch --> Phaser --> LoadProg -- SELECT --> DB
    LoadProg --> Movement
    Movement --> Collision
    Collision -- Yes --> Battle --> API -- AJAX --> Modal
    Collision -- No --> Movement
    
    Movement -.-> Shop
    Shop -- Purchase --> MgrUpdates
    Shop -- Save --> SaveScript

    Modal --> Answer
    Answer -- Yes --> Reward --> MgrUpdates -- UPDATE --> DB
    Answer -- No --> Penalty --> ShowAns -- UPDATE --> DB
    
    MgrUpdates --> AutoSave
    ShowAns --> AutoSave
    AutoSave --> SaveScript -- UPDATE --> DB
    SaveScript -.-> Movement

    %% Teacher Flows
    TDash --> VocabMod --> VocabScript --> VocabCRUD -- CRUDS --> DB
    TDash --> LessonMod --> LessonScript --> LessonAct -- CRUDS --> DB
    TDash --> StudentMod --> StudentScript --> StudentQuery -- SELECT --> DB

    %% External
    OTP -.-> Mailer
    GameLaunch -.-> CDN
```

## How to View
You can view this diagram in any Markdown viewer that supports Mermaid.js (like GitHub, GitLab, VS Code, or Obsidian).

### Key Legend
- **Purple**: Authentication & Security
- **Blue**: Student Portal & Features
- **Highlighted Blue**: Core Gameplay Loop
- **Green**: Teacher Administration
- **Orange**: Database Persistence
- **Shapes**:
  - Rounded Box: Process/Page
  - Cylinder: Database
  - Diamond: Decision Point
  - Dashed Line: Async/AJAX connection

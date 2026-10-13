local userId = %userId%
local baseUrl = %baseurl%
local x, y = %width%, %height%

-- Services
local Players = game:GetService("Players")
local ContentProvider = game:GetService("ContentProvider")
local ScriptContext = game:GetService("ScriptContext")
local ThumbnailGenerator = game:GetService("ThumbnailGenerator")
local RunService = game:GetService("RunService")

ContentProvider:SetBaseUrl(baseUrl)
ScriptContext.ScriptsDisabled = true

-- Load Character + Appearance
local Player = Players:CreateLocalPlayer(%target%)
Player.CharacterAppearance = ("%s/CharacterFetch.aspx?Id=%d"):format(baseUrl, userId)
Player:LoadCharacter(false)

-- Fix some stuff
local Character = Player.Character
if Character:FindFirstChild("Animate") then Character.Animate.Disabled = true end
if Character:FindFirstChild("Torso") then Character.Torso.Anchored = true end
if Character:FindFirstChild("LowerTorso") then Character.LowerTorso.Anchored = true end -- if someone tries adding R15

-- Headshot Camera
local FOV = 52.5
local AngleOffsetX = 0
local AngleOffsetY = 0
local AngleOffsetZ = 0

local CameraAngle = Character.Head.CFrame * CFrame.new(AngleOffsetX, AngleOffsetY, AngleOffsetZ)
local CameraPosition = Character.Head.CFrame + Vector3.new(0,0,0) + (CFrame.Angles(0, -0.2, 0).LookVector.unit * 3)

local Camera = Instance.new("Camera")
Camera.Parent = Character
Camera.Name = "ThumbnailCamera"
Camera.CameraType = Enum.CameraType.Scriptable
Camera.CoordinateFrame = CFrame.new(CameraPosition.p, CameraAngle.p)
Camera.FieldOfView = FOV

workspace.CurrentCamera = Camera

local result, requestedUrls = ThumbnailGenerator:Click("PNG", x, y, --[[hideSky = ]] true, --[[crop =]] true)
return result, requestedUrls

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

local gear = Player.Backpack:GetChildren()[1]
if gear and Character:FindFirstChild("Torso") then
    gear.Parent = Character
    character.Torso:FindFirstChild("Right Shoulder").CurrentAngle = math.rad(90)
end

local result, requestedUrls = ThumbnailGenerator:Click("PNG", x, y, --[[hideSky = ]] true, --[[crop =]] false)
return result, requestedUrls

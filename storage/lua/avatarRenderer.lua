-- Services
local Players, ContentProvider, ScriptContext, ThumbnailGenerator = game:GetService("Players"), game:GetService("ContentProvider"), game:GetService("ScriptContext"), game:GetService("ThumbnailGenerator")
ContentProvider:SetBaseurl(%baseurl%) // was http://localhost
ScriptContext.ScriptsDisabled = true

-- Make Thumbnail
local Player = Players:CreateLocalPlayer(%target%)
if Player == nil then return "" end
Player:LoadCharacter()

return ThumbnailGenerator:Click("PNG", 500, 500, true)

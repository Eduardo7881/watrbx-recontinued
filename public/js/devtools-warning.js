function Header() {
    const logo = `
██╗    ██╗ █████╗ ████████╗██████╗ ██████╗ ██╗  ██╗
██║    ██║██╔══██╗╚══██╔══╝██╔══██╗██╔══██╗╚██╗██╔╝
██║ █╗ ██║███████║   ██║   ██████╔╝██████╔╝ ╚███╔╝
██║███╗██║██╔══██║   ██║   ██╔══██╗██╔══██╗ ██╔██╗
╚███╔███╔╝██║  ██║   ██║   ██████╔╝██║  ██║██╔╝ ██╗
 ╚══╝╚══╝ ╚═╝  ╚═╝   ╚═╝   ╚═════╝ ╚═╝  ╚═╝╚═╝  ╚═╝
`;

    console.log("%c" + logo, "color: #ffffff; font-weight: bold;");
    console.log("%c WatrBX Recontinued (v1.1.0)", "color: #aaaaaa; font-size: 12px;");
    console.log("\n");
    console.log("%c ===== WARNING =====", "color: #ff4444; font-size: 22px; font-weight: bold;");
    console.log("%c Pasting unknown code into the Developer Console can compromise your account.", "color: #ffffff; font-size: 13px;");
    console.log("%c Malicious code may attempt to steal your .ROBLOSECUITY cookie, giving them full access to your account, robux and tix.", "color: #ff7777; font-size: 13px;");
}

if (typeof document !== "undefined") {
    document.addEventListener("load", Header);
} else {
    Header(); // node.js
}

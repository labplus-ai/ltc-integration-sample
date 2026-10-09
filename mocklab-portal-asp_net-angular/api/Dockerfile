FROM mcr.microsoft.com/dotnet/sdk:10.0

WORKDIR /app

# Restore packages first (cached unless the project file changes).
COPY *.csproj ./
RUN dotnet restore

COPY . .

ENV ASPNETCORE_URLS=http://0.0.0.0:3001 \
    DOTNET_NOLOGO=1 \
    DOTNET_CLI_TELEMETRY_OPTOUT=1

EXPOSE 3001
# Restarts the server automatically when the code changes.
CMD ["dotnet", "watch", "run", "--no-launch-profile"]
